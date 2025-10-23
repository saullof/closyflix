<div class="modal fade" tabindex="-1" role="dialog" id="noxpayQrcodeModal">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Verify Payment') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @php
                    $noxpayData = Session::get('noxpay_payment_data');
                @endphp
                @if($noxpayData && Auth::check() && data_get($noxpayData, 'user_id') === Auth::id())
                    <div class="mt-4 text-center">
                        @if (!empty($noxpayData['noxpay_payment_url']))
                            <div class="noxpay-checkout-frame-wrapper">
                                <iframe
                                    src="{{ $noxpayData['noxpay_payment_url'] }}"
                                    class="noxpay-checkout-frame"
                                    allow="payment *; clipboard-write"
                                    allowpaymentrequest
                                    sandbox="allow-scripts allow-forms allow-same-origin"
                                ></iframe>
                            </div>
                            <p class="small text-muted mt-3 mb-1">{{ __('Complete the checkout above to finish your payment.') }}</p>
                            <p class="small mb-2">
                                <a href="{{ $noxpayData['noxpay_payment_url'] }}" target="_blank" rel="noopener" class="btn btn-link p-0">{{ __('Open checkout in a new window') }}</a>
                            </p>
                            @if(!empty($noxpayData['noxpay_checkout_status']))
                                <p class="small text-muted mb-0">
                                    {{ __('Current status: :status', ['status' => strtoupper($noxpayData['noxpay_checkout_status'])]) }}
                                    @if(!empty($noxpayData['noxpay_checkout_substatus']))
                                        <br>{{ __('Step: :step', ['step' => strtoupper($noxpayData['noxpay_checkout_substatus'])]) }}
                                    @endif
                                </p>
                            @endif
                        @else
                            @if (!empty($noxpayData['noxpay_qr_code']))
                                <img src="data:image/png;base64,{{$noxpayData['noxpay_qr_code']}}" alt="NoxPay QR" style="max-width: 140px;">
                            @elseif (!empty($noxpayData['noxpay_qr_code_text']))
                                {!! QrCode::size(140)->generate($noxpayData['noxpay_qr_code_text']) !!}
                            @endif
                            <p>
                                <a href="javascript:void(0)" onclick="copyNoxpayPaymentCode('{{ $noxpayData['noxpay_qr_code_text'] ?? $noxpayData['noxpay_payment_code'] }}')" data-noxpay-payment-code="{{ $noxpayData['noxpay_qr_code_text'] ?? $noxpayData['noxpay_payment_code'] }}" class="btn btn-link mr-0 mt-4">{{ __('Scan the QR Code Or Click to copy code & Verify Payment') }}</a>
                            </p>
                        @endif
                    </div>

                    @php
                        $transaction = \App\Model\Transaction::where('id', $noxpayData['transaction_id'])->first();

                        if ($transaction && $transaction->created_at->diffInMinutes(now()) > 2) {
                            Session::forget('noxpay_payment_data');
                        }
                    @endphp
                @endif
            </div>
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>
            .noxpay-checkout-frame-wrapper {
                width: 100%;
                position: relative;
                padding-top: 150%;
            }
            .noxpay-checkout-frame {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                border: 0;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            @media (min-width: 576px) {
                .noxpay-checkout-frame-wrapper {
                    padding-top: 135%;
                }
            }
        </style>
    @endpush
@endonce
