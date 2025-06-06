<!-- 1) jQuery (antes de tudo) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- 2) Inputmask -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.8/jquery.inputmask.min.js"></script>

<script>
$(function(){
  var $phone     = $('#paymentPhone');
  var $submitBtn = $('.checkout-continue-btn');

  // Função que formata em (00) 00000-0000
  function formatPhone(value) {
    var d = value.replace(/\D/g, '').slice(0, 11);
    var res = '';
    if (d.length > 0)       res += '(' + d.substring(0, Math.min(2, d.length));
    if (d.length >= 2)      res += ') ';
    if (d.length > 2)       res += d.substring(2, Math.min(7, d.length));
    if (d.length >= 7)      res += '-' + d.substring(7, d.length);
    return res;
  }

  // 1) Formata enquanto digita
  $phone.on('input', function(){
    this.value = formatPhone(this.value);
  });

  // 2) Checagem a cada segundo: aplica classes e botão disable/enable
  setInterval(function(){
    var digits = $phone.val().replace(/\D/g, '');

    if (digits.length === 11) {
      $phone.removeClass('is-invalid').addClass('is-valid');
      $submitBtn.prop('disabled', false);
    } else {
      $phone.removeClass('is-valid').addClass('is-invalid');
      $submitBtn.prop('disabled', true);
    }
  }, 1000);

  // 3) No submit: bloqueia se inválido e popula hidden
  $('#pp-buyItem').on('submit', function(e){
    var digits = $phone.val().replace(/\D/g, '');
    if (digits.length !== 11) {
      e.preventDefault();
      $phone.addClass('is-invalid').focus();
      return false;
    }
    // preenche o hidden e envia
    $('#payment-user-phone').val(digits);
  });
});
</script>


<div class="row checkout-dialog">
    <div class="col-lg-6 mx-auto">
        {{-- Paypal and stripe actual buttons --}}
        <div class="paymentOption paymentPP d-none">
            <form id="pp-buyItem" method="post" action="{{route('payment.initiatePayment')}}">
                @csrf
                <input type="hidden" name="amount" id="payment-deposit-amount" value="">
                <input type="hidden" name="transaction_type" id="payment-type" value="">
                <input type="hidden" name="post_id" id="post" value="">
                <input type="hidden" name="user_message_id" id="userMessage" value="">
                <input type="hidden" name="recipient_user_id" id="recipient" value="">
                <input type="hidden" name="provider" id="provider" value="">
                <input type="hidden" name="first_name" id="paymentFirstName" value="">
                <input type="hidden" name="last_name" id="paymentLastName" value="">
                <input type="hidden" name="billing_address" id="paymentBillingAddress" value="">
                <input type="hidden" name="city" id="paymentCity" value="">
                <input type="hidden" name="state" id="paymentState" value="">
                <input type="hidden" name="postcode" id="paymentPostcode" value="">
                <input type="hidden" name="country" id="paymentCountry" value="">
                <input type="hidden" name="taxes" id="paymentTaxes" value="">
                <input type="hidden" name="stream" id="stream" value="">
                <input type="hidden" name="user_phone" id="payment-user-phone" value="">
                <button class="payment-button" type="submit"></button>
            </form>
        </div>

        <div class="paymentOption ml-2 paymentStripe d-none">
            <button id="stripe-checkout-button">{{__('Checkout')}}</button>
        </div>

        <!-- Modal -->
        <div class="checkout-popup modal fade" id="checkout-center" tabindex="-1" role="dialog" aria-labelledby="checkout" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="payment-title"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{__('Close')}}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="payment-body">
                            <div class="d-flex flex-row">
                                <div class="ml-0 ml-md-2 mb-2">
                                    <img src="" class="rounded-circle user-avatar">
                                </div>
                                <div class="d-lg-block">
                                    <div class="pl-2 d-flex justify-content-center flex-column">
                                        <div class="ml-2 ">
                                            <div class="text-bold {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}} name"></div>
                                            <div class="text-muted username"><span>@</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="payment-description mb-3 d-none"></div>
                            <div class="input-group mb-3 checkout-amount-input d-none">
                                <div class="input-group-prepend">
                            <span class="input-group-text" id="amount-label">
                                @include('elements.icon',['icon'=>'cash-outline','variant'=>'medium','centered'=>false])
                            </span>
                                </div>
                                <input class="form-control uifield-amount" placeholder="{{__(\App\Providers\SettingsServiceProvider::leftAlignedCurrencyPosition() ? 'Amount ($5 min, $500 max)' : 'Amount (5$ min, 500$ max)',['min'=>getSetting('payments.min_tip_value'),'max'=>getSetting('payments.max_tip_value'),'currency'=>config('app.site.currency_symbol')])}}" aria-label="Username" aria-describedby="amount-label" id="checkout-amount" type="number" min="0" step="1" max="500" >
                                <div class="invalid-feedback">{{__('Please enter a valid amount.')}}</div>
                            </div>
                        </div>

                        <div id="accordion" class="mb-3">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between" id="headingOne" data-toggle="collapse" data-target="#billingInformation" aria-expanded="true" aria-controls="billingInformation">
                                    <h6 class="mb-0">
                                        {{__('Billing agreement details')}}
                                    </h6>
                                    <div class="ml-1 label-icon">
                                        @include('elements.icon',['icon'=>'chevron-down-outline','centered'=>false])
                                    </div>
                                </div>
                                <div id="billingInformation" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                                    <div class="card-body">
                                        <form id="billing-agreement-form">
                                            <div class="tab-content">
                                                <!-- credit card info-->
                                                <div id="individual" class="tab-pane fade show active pt-1">
                                                    <div class="row form-group">
                                                        <div class="col-sm-6 col-6">
                                                            <div class="form-group">
                                                                <label for="firstName">
                                                                    <span>{{__('First name')}}</span>
                                                                </label>
                                                                <input type="text" name="firstName" placeholder="{{__('First name')}}" onchange="checkout.validateFirstNameField();" required class="form-control uifield-first_name">
                                                            </div>

                                                        </div>
                                                        <div class="col-sm-6 col-6">
                                                            <div class="form-group">
                                                                <label for="lastName">
                                                                    <span>{{__('Last name')}}</span>
                                                                </label>
                                                                <input type="text" name="lastName" placeholder="{{__('Last name')}}" onblur="checkout.validateLastNameField()" required class="form-control uifield-last_name">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="countrySelect">
                                                            <span>{{__('Country')}}</span>
                                                        </label>
                                                        <select class="country-select form-control input-sm uifield-country" id="countrySelect" required onchange="checkout.validateCountryField()"></select>
                                                    </div>
                                                    <div class="row form-group">
                                                        <div class="col-sm-6 col-6">
                                                            <div class="form-group">
                                                                <label for="phone">
                                                                    <span>{{__('Phone')}}</span>
                                                                </label>
                                                                <input
                                                                    type="tel"
                                                                    id="paymentPhone"
                                                                    name="phone"
                                                                    class="form-control uifield-phone"
                                                                    placeholder="(00) 00000-0000"
                                                                    required
                                                                    onchange="checkout.validatePhoneField();"
                                                                >
                                                                <div class="valid-feedback">
                                                                    Número Válido!
                                                                </div>
                                                                <div class="invalid-feedback">
                                                                    Número Inválido!
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                                <div class="billing-agreement-error error text-danger d-none">{{__('Please complete all billing details')}}</div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h6>{{__('Payment summary')}}</h6>
                            <div class="subtotal row">
                                <span class="col-sm left"><b>{{__('Subtotal')}}:</b></span>
                                <span class="subtotal-amount col-sm right text-right">
                                        <b>$0.00</b>
                                    </span>
                            </div>
                            <div class="taxes row">
                                <span class="col-sm left"><b>{{__('Taxes')}}</b></span>
                            </div>
                            <div class="taxes-details"></div>
                            <div class="total row">
                                <span class="col-sm left"><b>{{__('Total')}}:</b></span>
                                <span class="total-amount col-sm right text-right">
                                        <b>$0.00</b>
                                    </span>
                            </div>
                        </div>

                        <div>
                            <h6>{{__('Payment method')}}</h6>
                            <div class="d-flex text-left radio-group row px-2">
                                @if(getSetting('payments.stripe_secret_key') && getSetting('payments.stripe_public_key') && !getSetting('payments.stripe_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 stripe-payment-method" >
                                        <div class="radio mx-auto stripe-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="stripe">
                                            <img src="{{asset('/img/logos/stripe.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(config('paypal.client_id') && config('paypal.secret') && !getSetting('payments.paypal_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 paypal-payment-method">
                                        <div class="radio mx-auto paypal-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="paypal">
                                            <img src="{{asset('/img/logos/paypal.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.coinbase_api_key') && !getSetting('payments.coinbase_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none coinbase-payment-method">
                                        <div class="radio mx-auto coinbase-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="coinbase">
                                            <img src="{{asset('/img/logos/coinbase.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.nowpayments_api_key') && !getSetting('payments.nowpayments_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none nowpayments-payment-method">
                                        <div class="radio mx-auto nowpayments-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="nowpayments">
                                            <img src="{{asset('/img/logos/nowpayments.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(\App\Providers\PaymentsServiceProvider::ccbillCredentialsProvided())
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none ccbill-payment-method">
                                        <div class="radio mx-auto ccbill-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="ccbill">
                                            <img src="{{asset('/img/logos/ccbill.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.paystack_secret_key') && !getSetting('payments.paystack_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none paystack-payment-method">
                                        <div class="radio mx-auto paystack-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="paystack">
                                            <img src="{{asset('/img/logos/paystack.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.stripe_secret_key') && getSetting('payments.stripe_public_key') && !getSetting('payments.stripe_checkout_disabled') && getSetting('payments.stripe_oxxo_provider_enabled'))
                                        <div class="p-1 col-6 col-md-3 col-lg-3 col-md-3 d-none oxxo-payment-method">
                                        <div class="radio mx-auto oxxo-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="oxxo">
                                            <img src="{{asset('/img/logos/oxxo.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.mercado_access_token') && !getSetting('payments.mercado_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 d-none mercado-payment-method">
                                        <div class="radio mx-auto mercado-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="mercado">
                                            <img src="{{asset('/img/logos/mercado.svg')}}">
                                        </div>
                                    </div>
                                @endif
                                
                                @if (config('services.suitpay.enabled'))
                                    <div class="p-1 col-6 col-md-3 d-none suitpay-payment-method">
                                        <div class="radio mx-auto suitpay-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="suitpay">
                                            <img src="{{asset('/img/logos/pix.png')}}">
                                        </div>
                                    </div>
                                @endif

                                <div class="credit-payment-method p-1 col-6 col-md-3 col-lg-3 col-md-3" {!! !Auth::check() || Auth::user()->wallet->total <= 0 ? 'data-toggle="tooltip" data-placement="right"' : '' !!} title="{{__('You can use the wallet deposit page to add credit.')}}">
                                    <div class="radio mx-auto credit-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="credit">
                                        <div class="credit-provider-text">
                                            <b>{{__("Credit")}}</b>
                                            <div class="available-credit">({{\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount('0')}})</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="payment-error error text-danger text-bold d-none mb-1">{{__('Please select your payment method')}}</div>
                        <p class="text-muted mt-1"> {{__('Note: After clicking on the button, you will be directed to a secure gateway for payment. After completing the payment process, you will be redirected back to the website.')}} </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('Cancel')}}</button>
                        <button type="submit" class="btn btn-primary checkout-continue-btn">{{__('Continue')}}
                            <div class="spinner-border spinner-border-sm ml-2 d-none" role="status">
                                <span class="sr-only">{{__('Loading...')}}</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


{{--  suitpay qrcode  --}}
<div class="modal fade" tabindex="-1" role="dialog" id="suitpayQrcodeModal">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Verify Payment') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{__('Close')}}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if (Session::has('suitpay_payment_data') && Session::get('suitpay_payment_data')['user_id'] == Auth::user()->id)
                    <div class="mt-4 text-center">
                        {!! QrCode::size(140)->generate(Session::get('suitpay_payment_data')['suitpay_payment_code']) !!}
                        <p>
                            <a href="javascript:void(0)" onclick="copySuitpayPaymentCode('{{ Session::get('suitpay_payment_data')['suitpay_payment_code'] }}')" data-suitpay-payment-code="{{ Session::get('suitpay_payment_data')['suitpay_payment_code'] }}" class="btn btn-link  mr-0 mt-4">{{__('Scan the QR Code Or Click to copy code & Verify Payment')}}</a>
                        </p>
                    </div>

                    @php
                        if (Session::has('suitpay_payment_data')) {
                            $transaction = \App\Model\Transaction::where('id', Session::get('suitpay_payment_data')['transaction_id'])->first();

                            if ($transaction->created_at->diffInMinutes(now()) > 2) {
                                Session::forget('suitpay_payment_data');
                            }
                        }
                    @endphp
                @endif
            </div>
        </div>
    </div>
</div>



