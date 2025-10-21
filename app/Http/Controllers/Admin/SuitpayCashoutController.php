<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;

class SuitpayCashoutController extends Controller
{
    public function index()
    {
        if (! $this->hasSuitpayPixColumns()) {
            $withdrawals = new LengthAwarePaginator([], 0, 20, null, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);

            return Voyager::view('vendor.voyager.suitpay.cashouts.index', [
                'withdrawals' => $withdrawals,
                'missingPixColumns' => true,
            ]);
        }

        $withdrawals = Withdrawal::query()
            ->with('user')
            ->where(function ($query) {
                $query->whereNotNull('pix_key_type')
                    ->orWhere('payment_method', 'LIKE', '%PIX%');
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return Voyager::view('vendor.voyager.suitpay.cashouts.index', [
            'withdrawals' => $withdrawals,
        ]);
    }

    public function store(Request $request, Withdrawal $withdrawal)
    {
        if (! $this->hasSuitpayPixColumns()) {
            return back()->withErrors([
                'cashout' => __('Execute as migrações pendentes antes de processar cash-outs pela SuitPay.'),
            ]);
        }

        $this->ensureSuitpayConfigured();

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
        $withdrawal->pix_key_type = $normalizedType;
        $withdrawal->pix_beneficiary_name = $validated['pix_beneficiary_name'];
        $withdrawal->pix_document = $sanitizedDocument;

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

        $withdrawal->suitpay_cashout_payload = $payload;
        $withdrawal->suitpay_cashout_requested_at = Carbon::now();
        $withdrawal->suitpay_cashout_error = null;

        if (! $this->hasSuitpayTrackingColumns()) {
            return back()->withErrors([
                'cashout' => __('Execute as migrações pendentes antes de processar cash-outs pela SuitPay.'),
            ])->withInput();
        }

        $response = Http::withHeaders([
            'ci' => config('services.suitpay.client_id'),
            'cs' => config('services.suitpay.client_secret'),
        ])->post('https://ws.suitpay.app/api/v1/gateway/pix-payment', $payload);

        if ($response->failed()) {
            $withdrawal->suitpay_cashout_status = 'ERROR';
            $withdrawal->suitpay_cashout_response = ['body' => $response->body(), 'status' => $response->status()];
            $withdrawal->suitpay_cashout_error = $response->json('message', $response->body());
            $withdrawal->save();

            return back()->withErrors([
                'cashout' => __('Falha ao solicitar cash-out na SuitPay: :error', ['error' => $withdrawal->suitpay_cashout_error]),
            ])->withInput();
        }

        $data = $response->json();
        $cashoutId = Arr::get($data, 'idTransaction');
        $cashoutStatus = Arr::get($data, 'response');

        $withdrawal->suitpay_cashout_id = $cashoutId;
        $withdrawal->suitpay_cashout_status = $cashoutStatus;
        $withdrawal->suitpay_cashout_response = $data;
        $withdrawal->save();

        return back()->with([
            'suitpay_cashout_success' => __('Cash-out enviado para a SuitPay com sucesso. ID da transação: :id', [
                'id' => $cashoutId ?? $withdrawal->suitpay_cashout_id ?? __('não informado'),
            ]),
        ]);
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

        $withdrawal->suitpay_cashout_status = $status;
        $withdrawal->suitpay_cashout_response = $payload;

        if ($normalizedStatus && in_array($normalizedStatus, ['PAID_OUT', 'PAID', 'OK'], true)) {
            if ($withdrawal->status !== Withdrawal::APPROVED_STATUS) {
                $withdrawal->status = Withdrawal::APPROVED_STATUS;
            }

            if (! $withdrawal->suitpay_cashout_processed_at) {
                $withdrawal->suitpay_cashout_processed_at = Carbon::now();
            }
        }

        if ($normalizedStatus && in_array($normalizedStatus, ['NO_FUNDS', 'ACCOUNT_DOCUMENTS_NOT_VALIDATED', 'PIX_KEY_NOT_FOUND', 'DOCUMENT_VALIDATE', 'ERROR'], true)) {
            $withdrawal->suitpay_cashout_error = $payload['message'] ?? $normalizedStatus;
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
        return Schema::hasColumn('withdrawals', 'pix_key_type')
            && Schema::hasColumn('withdrawals', 'pix_beneficiary_name')
            && Schema::hasColumn('withdrawals', 'pix_document');
    }

    protected function hasSuitpayTrackingColumns(): bool
    {
        $requiredColumns = [
            'suitpay_cashout_payload',
            'suitpay_cashout_status',
            'suitpay_cashout_id',
            'suitpay_cashout_requested_at',
            'suitpay_cashout_processed_at',
            'suitpay_cashout_error',
            'suitpay_cashout_response',
        ];

        foreach ($requiredColumns as $column) {
            if (! Schema::hasColumn('withdrawals', $column)) {
                return false;
            }
        }

        return true;
    }
}
