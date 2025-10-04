<?php 

namespace App\Services;

use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SuitPay
{
    public static function generatePixPaymentCode($data)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sandbox.ws.suitpay.app/api/v1/gateway/request-qrcode',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'ci: '.config('services.suitpay.client_id'),
                'cs: '.config('services.suitpay.client_secret'),
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;

    }

    public static function requestPixCashout(array $payload): array
    {
        try {
            $response = Http::withHeaders(self::defaultHeaders())
                ->post(self::gatewayUrl('pix-payment'), $payload);
        } catch (\Throwable $exception) {
            Log::error('SuitPay PIX cash-out request exception: '.$exception->getMessage(), [
                'context' => self::summarizePayload($payload),
            ]);

            return [
                'success' => false,
                'status' => null,
                'body' => null,
                'error' => $exception->getMessage(),
            ];
        }

        $body = self::decodeResponse($response);

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $body,
            'error' => $response->successful() ? null : ($body['message'] ?? $response->body()),
        ];
    }

    public static function getPixCashoutReceipt(string $transactionId): array
    {
        try {
            $response = Http::withHeaders(self::defaultHeaders())
                ->get(self::gatewayUrl('get-receipt-pix-cashout'), [
                    'idTransaction' => $transactionId,
                ]);
        } catch (\Throwable $exception) {
            Log::error('SuitPay PIX cash-out receipt exception: '.$exception->getMessage(), [
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => false,
                'status' => null,
                'body' => null,
                'error' => $exception->getMessage(),
            ];
        }

        $body = self::decodeResponse($response);

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $body,
            'error' => $response->successful() ? null : ($body['message'] ?? $response->body()),
        ];
    }

    protected static function defaultHeaders(): array
    {
        return [
            'ci' => config('services.suitpay.client_id'),
            'cs' => config('services.suitpay.client_secret'),
            'Accept' => 'application/json',
        ];
    }

    protected static function gatewayUrl(string $path): string
    {
        $base = rtrim(config('services.suitpay.base_url', 'https://ws.suitpay.app/api/v1/gateway'), '/');

        return $base.'/'.ltrim($path, '/');
    }

    protected static function decodeResponse(HttpResponse $response): ?array
    {
        try {
            return $response->json();
        } catch (\Throwable $exception) {
            Log::warning('SuitPay response JSON decode failed: '.$exception->getMessage());

            return null;
        }
    }

    protected static function summarizePayload(array $payload): array
    {
        return [
            'typeKey' => $payload['typeKey'] ?? null,
            'externalId' => $payload['externalId'] ?? null,
            'value' => $payload['value'] ?? null,
        ];
    }
}