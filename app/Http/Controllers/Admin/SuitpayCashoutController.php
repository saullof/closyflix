<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use App\Model\Withdrawal;
use App\Services\SuitPay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SuitpayCashoutController extends Controller
{
    /**
     * Display the SuitPay cash-out dashboard.
     */
    public function index()
    {
        $pendingWithdrawals = Withdrawal::with('user')
            ->where('status', Withdrawal::REQUESTED_STATUS)
            ->orderBy('created_at')
            ->paginate(10);

        $recentWithdrawals = Withdrawal::with('user')
            ->whereNotNull('suitpay_cashout_transaction_id')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $pixKeyTypes = [
            'document' => 'CPF / CNPJ',
            'phoneNumber' => 'Telefone',
            'email' => 'E-mail',
            'randomKey' => 'Chave aleatória',
            'paymentCode' => 'Código de pagamento',
        ];

        return view('admin.suitpay.cashouts.index', compact('pendingWithdrawals', 'recentWithdrawals', 'pixKeyTypes'));
    }

    /**
     * Execute a cash-out via SuitPay for the selected withdrawal.
     */
    public function execute(Request $request, Withdrawal $withdrawal, PaymentHelper $paymentHelper)
    {
        $validated = $request->validate([
            'pix_key' => ['required', 'string', 'max:255'],
            'pix_key_type' => ['required', Rule::in(['document', 'phoneNumber', 'email', 'randomKey', 'paymentCode'])],
            'pix_document' => ['nullable', 'string', 'max:32'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'context_withdrawal_id' => ['nullable', 'integer'],
        ]);

        $withdrawal->fill([
            'pix_key' => $validated['pix_key'],
            'pix_key_type' => $validated['pix_key_type'],
            'pix_document' => $validated['pix_document'] ?? null,
        ]);

        $result = $paymentHelper->processSuitpayPixCashout($withdrawal, [
            'key' => $validated['pix_key'],
            'typeKey' => $validated['pix_key_type'],
            'value' => $validated['value'],
            'documentValidation' => $validated['pix_document'] ?? null,
        ]);

        $withdrawal->save();

        if (!$result['success']) {
            $message = $result['message'] ?? __('Unable to process the PIX cash-out with SuitPay.');

            return redirect()
                ->back()
                ->withInput()
                ->with([
                    'message' => $message,
                    'alert-type' => 'error',
                ]);
        }

        // Update status so the observer flow runs (transaction + notifications)
        $withdrawal->status = Withdrawal::APPROVED_STATUS;
        $withdrawal->processed = false;
        $withdrawal->save();

        return redirect()
            ->route('admin.suitpay.cashouts')
            ->with([
                'message' => __('SuitPay cash-out initiated successfully. Protocol: :protocol', [
                    'protocol' => $withdrawal->suitpay_cashout_transaction_id,
                ]),
                'alert-type' => 'success',
            ]);
    }

    /**
     * Download or fetch the SuitPay cash-out receipt.
     */
    public function receipt(Withdrawal $withdrawal)
    {
        if (!$withdrawal->suitpay_cashout_transaction_id) {
            abort(404);
        }

        $pdfBase64 = $withdrawal->suitpay_cashout_receipt;

        if (!$pdfBase64) {
            $response = SuitPay::getPixCashoutReceipt($withdrawal->suitpay_cashout_transaction_id);

            if (!$response['success']) {
                $message = $response['error'] ?? __('Unable to fetch the cash-out receipt at this time.');
                Log::warning('SuitPay cash-out receipt failed', [
                    'withdrawal_id' => $withdrawal->id,
                    'http_status' => $response['status'] ?? null,
                    'error' => $message,
                ]);

                return redirect()
                    ->back()
                    ->with([
                        'message' => $message,
                        'alert-type' => 'error',
                    ]);
            }

            $body = $response['body'] ?? [];
            $pdfBase64 = $body['pdfBase64'] ?? null;

            if (!$pdfBase64) {
                return redirect()
                    ->back()
                    ->with([
                        'message' => __('SuitPay did not return a valid PDF receipt.'),
                        'alert-type' => 'error',
                    ]);
            }

            $withdrawal->suitpay_cashout_receipt = $pdfBase64;
            $withdrawal->suitpay_cashout_receipt_generated_at = now();
            $withdrawal->save();
        }

        $binary = base64_decode($pdfBase64, true);

        if ($binary === false) {
            return redirect()
                ->back()
                ->with([
                    'message' => __('Stored receipt is corrupted or invalid.'),
                    'alert-type' => 'error',
                ]);
        }

        return response($binary)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="suitpay-cashout-'.$withdrawal->id.'.pdf"');
    }
}
