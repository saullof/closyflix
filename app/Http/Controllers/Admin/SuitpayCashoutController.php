<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;

class SuitpayCashoutController extends Controller
{
    public function index()
    {
        $hasPixColumns = $this->hasSuitpayPixColumns();
        $hasTrackingColumns = $this->hasSuitpayTrackingColumns();

        $withdrawalsQuery = Withdrawal::query()
            ->with('user')
            ->orderByDesc('created_at');

        if ($hasPixColumns) {
            $withdrawalsQuery->where(function ($query) {
                $query->whereNotNull('pix_key_type')
                    ->orWhere('payment_method', 'LIKE', '%PIX%');
            });
        } else {
            $withdrawalsQuery->where('payment_method', 'LIKE', '%PIX%');
        }

        $withdrawals = $withdrawalsQuery->paginate(20);

        return Voyager::view('vendor.voyager.suitpay.cashouts.index', [
            'withdrawals' => $withdrawals,
            'missingPixColumns' => ! $hasPixColumns,
            'missingTrackingColumns' => ! $hasTrackingColumns,
        ]);
    }

    public function store(Request $request, Withdrawal $withdrawal)
    {
        $this->ensureSuitpayConfigured();

        $hasPixColumns = $this->hasSuitpayPixColumns();
        $hasTrackingColumns = $this->hasSuitpayTrackingColumns();

        $validated = $request->validate([
            'payment_identifier' => 'required|string',
            'pix_key_type' => 'required|string',
            'pix_beneficiary_name' => 'required|string',
            'pix_document' => 'nullable|string',
        ]);

        $normalizedType = Str::lower($validated['pix_key_type']);
        $typeKey = $this->mapPixKeyType($normalizedType);

        if (! $typeKey) {
            return back()->withErrors([
                'pix_key_type' => __('Tipo de chave Pix inválido para integração com a SuitPay.'),
            ])->withInput();
        }

        $sanitizedDocument = $this->sanitizePixDocument($validated['pix_document'] ?? null);
        $withdrawal->payment_identifier = $validated['payment_identifier'];

        if ($hasPixColumns) {
            $withdrawal->pix_key_type = $normalizedType;
            $withdrawal->pix_beneficiary_name = $validated['pix_beneficiary_name'];
            $withdrawal->pix_document = $sanitizedDocument;
        }

        $payload = [
            'value' => round((float) $withdrawal->amount, 2),
            'key' => $withdrawal->payment_identifier,
            'typeKey' => $typeKey,
            'callbackUrl' => route('suitpay.cashouts.webhook'),
            'externalId' => (string) $withdrawal->id,
        ];

        if (! empty($sanitizedDocument)) {
            $payload['documentValidation'] = $sanitizedDocument;
        }

        if ($hasTrackingColumns) {
            $withdrawal->suitpay_cashout_payload = $payload;
            $withdrawal->suitpay_cashout_requested_at = Carbon::now();
            $withdrawal->suitpay_cashout_error = null;
        }

        $withdrawal->save();

        $response = Http::withHeaders([
            'ci' => config('services.suitpay.client_id'),
            'cs' => config('services.suitpay.client_secret'),
        ])->post('https://ws.suitpay.app/api/v1/gateway/pix-payment', $payload);

        if ($response->failed()) {
            if ($hasTrackingColumns) {
                $withdrawal->suitpay_cashout_status = 'ERROR';
                $withdrawal->suitpay_cashout_response = ['body' => $response->body(), 'status' => $response->status()];
                $withdrawal->suitpay_cashout_error = $response->json('message', $response->body());
                $withdrawal->save();
            } else {
                Log::warning('SuitPay cash-out response could not be persisted because tracking columns are missing.', [
                    'withdrawal_id' => $withdrawal->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return back()->withErrors([
                'cashout' => __('Falha ao solicitar cash-out na SuitPay: :error', ['error' => $withdrawal->suitpay_cashout_error ?? $response->body()]),
            ])->withInput();
        }

        $data = $response->json();
        $cashoutId = Arr::get($data, 'idTransaction');
        $cashoutStatus = Arr::get($data, 'response');

        if ($hasTrackingColumns) {
            $withdrawal->suitpay_cashout_id = $cashoutId;
            $withdrawal->suitpay_cashout_status = $cashoutStatus;
            $withdrawal->suitpay_cashout_response = $data;
            $withdrawal->save();
        } else {
            Log::info('SuitPay cash-out processed without persistence columns', [
                'withdrawal_id' => $withdrawal->id,
                'response' => $data,
            ]);
        }

        $flashData = [
            'suitpay_cashout_success' => __('Cash-out enviado para a SuitPay com sucesso. ID da transação: :id', [
                'id' => $cashoutId ?? $withdrawal->suitpay_cashout_id ?? __('não informado'),
            ]),
        ];

        if (! $hasPixColumns || ! $hasTrackingColumns) {
            $flashData['suitpay_cashout_warning'] = __('Cash-out enviado, mas ainda existem migrações pendentes para armazenar todos os dados da SuitPay. Execute as migrações assim que possível.');
        }

        return back()->with($flashData);
    }

    public function webhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        if (! is_array($payload)) {
            $payload = $request->all();
        }

        Log::info('SuitPay cashout webhook received', ['payload' => $payload]);

        $withdrawal = null;
        if (isset($payload['externalId'])) {
            $withdrawal = Withdrawal::find($payload['externalId']);
        }

        if (! $withdrawal && isset($payload['idTransaction'])) {
            $withdrawal = Withdrawal::where('suitpay_cashout_id', $payload['idTransaction'])->first();
        }

        if (! $withdrawal) {
            return response()->json(['status' => 'ignored']);
        }

        $status = $payload['statusTransaction'] ?? $payload['response'] ?? null;
        $normalizedStatus = $status ? Str::upper($status) : null;

        $hasTrackingColumns = $this->hasSuitpayTrackingColumns();

        if ($hasTrackingColumns) {
            $withdrawal->suitpay_cashout_status = $status;
            $withdrawal->suitpay_cashout_response = $payload;
        } else {
            Log::info('SuitPay cash-out webhook received without tracking columns', [
                'withdrawal_id' => $withdrawal->id,
                'status' => $status,
            ]);
        }

        if ($normalizedStatus && in_array($normalizedStatus, ['PAID_OUT', 'PAID', 'OK'], true)) {
            if ($withdrawal->status !== Withdrawal::APPROVED_STATUS) {
                $withdrawal->status = Withdrawal::APPROVED_STATUS;
            }

            if ($hasTrackingColumns && ! $withdrawal->suitpay_cashout_processed_at) {
                $withdrawal->suitpay_cashout_processed_at = Carbon::now();
            }
        }

        if ($normalizedStatus && in_array($normalizedStatus, ['NO_FUNDS', 'ACCOUNT_DOCUMENTS_NOT_VALIDATED', 'PIX_KEY_NOT_FOUND', 'DOCUMENT_VALIDATE', 'ERROR'], true)) {
            if ($hasTrackingColumns) {
                $withdrawal->suitpay_cashout_error = $payload['message'] ?? $normalizedStatus;
            } else {
                Log::warning('SuitPay cash-out error received but could not be persisted without tracking columns.', [
                    'withdrawal_id' => $withdrawal->id,
                    'status' => $normalizedStatus,
                    'message' => $payload['message'] ?? null,
                ]);
            }
        }

        $withdrawal->save();

        return response()->json(['status' => 'ok']);
    }

    protected function mapPixKeyType(?string $type): ?string
    {
        if (! $type) {
            return null;
        }

        $type = Str::lower($type);

        return match ($type) {
            'cpf', 'cnpj', 'document' => 'document',
            'email' => 'email',
            'phone', 'phonenumber' => 'phoneNumber',
            'random', 'randomkey' => 'randomKey',
            'paymentcode' => 'paymentCode',
            default => null,
        };
    }

    protected function sanitizePixDocument(?string $document): ?string
    {
        if (! $document) {
            return null;
        }

        return preg_replace('/[^0-9A-Za-z@._+-]/', '', $document);
    }

    protected function ensureSuitpayConfigured(): void
    {
        if (! config('services.suitpay.client_id') || ! config('services.suitpay.client_secret')) {
            abort(403, __('Configure as credenciais da SuitPay antes de processar cash-outs.'));
        }
    }

    protected function hasSuitpayPixColumns(): bool
    {
        return $this->withdrawalColumnsAvailable([
            'pix_key_type',
            'pix_beneficiary_name',
            'pix_document',
        ]);
    }

    protected function hasSuitpayTrackingColumns(): bool
    {
        return $this->withdrawalColumnsAvailable([
            'suitpay_cashout_payload',
            'suitpay_cashout_status',
            'suitpay_cashout_id',
            'suitpay_cashout_requested_at',
            'suitpay_cashout_processed_at',
            'suitpay_cashout_error',
            'suitpay_cashout_response',
        ]);
    }

    protected function withdrawalColumnsAvailable(array $columns): bool
    {
        static $columnCache = [];

        $cacheKey = implode('|', $columns);

        if (array_key_exists($cacheKey, $columnCache)) {
            return $columnCache[$cacheKey];
        }

        try {
            Withdrawal::query()
                ->select(array_merge(['id'], $columns))
                ->limit(1)
                ->first();

            return $columnCache[$cacheKey] = true;
        } catch (\Throwable $exception) {
            Log::warning('SuitPay cash-out columns unavailable', [
                'columns' => $columns,
                'error' => $exception->getMessage(),
            ]);

            return $columnCache[$cacheKey] = false;
        }
    }
}
