
@extends('layouts.generic')

@section('scripts')
    {!! Minify::javascript([
        '/js/pages/checkout.js'
    ])->withFullUrl() !!}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.8/jquery.inputmask.min.js"></script>


    <script>
        document.addEventListener('DOMContentLoaded', adjustCols);
        window.addEventListener('resize', adjustCols);

        function adjustCols() {
            // breakpoint “md” do Bootstrap = 768px
            const isMobile = window.innerWidth < 768;

            // Seleciona apenas as colunas que originalmente usavam col-md-4 dentro da row específica
            const cols = document.querySelectorAll('.row.justify-content-center.mt-5 .col-md-4');

            cols.forEach(col => {
                if (isMobile) {
                    col.classList.remove('col-md-4');
                } else {
                    // se precisar que volte no desktop, adicione de volta
                    if (!col.classList.contains('col-md-4')) {
                        col.classList.add('col-md-4');
                    }
                }
            });
        }
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Captura os elementos necessários
            const planRadios = document.querySelectorAll("input[name='subscription_plan']");
            const defaultButton = document.getElementById("default-subscribe-button");

            // Mapeamento dos planos com os botões correspondentes
            const planButtons = {
                "one-month-subscription": defaultButton,
                "three-months-subscription": document.getElementById("subscribe-button-90"),
                "six-months-subscription": document.getElementById("subscribe-button-182"),
                "yearly-subscription": document.getElementById("subscribe-button-365"),
            };

            // Esconde todos os botões de assinatura inicialmente
            function resetButtons() {
                Object.values(planButtons).forEach(button => {
                    if (button) button.classList.add("d-none");
                });
            }

            // Atualiza o botão mostrado com mudança de plano
            planRadios.forEach(radio => {
                radio.addEventListener("change", function() {
                    resetButtons();
                    const selectedPlan = this.value;
                    if (planButtons[selectedPlan]) {
                        planButtons[selectedPlan].classList.remove("d-none");
                    }
                });
            });

            // Garante que o botão inicial esteja correto na primeira carga
            const checkedRadio = document.querySelector("input[name='subscription_plan']:checked");
            if (checkedRadio) {
                resetButtons();
                if (planButtons[checkedRadio.value]) {
                    planButtons[checkedRadio.value].classList.remove("d-none");
                }
            }
        });

        function startTimer(duration, minutesDisplay, secondsDisplay) {
            let timer = duration, minutes, seconds;
            setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutesDisplay.textContent = minutes < 10 ? "0" + minutes : minutes;
                secondsDisplay.textContent = seconds < 10 ? "0" + seconds : seconds;

                if (--timer < 0) {
                    timer = 0; // Evita valores negativos
                }
            }, 1000);
        }

        window.onload = function () {
            let duration = 15 * 60, // 15 minutos em segundos
                minutesDisplay = document.querySelector('.minutes'),
                secondsDisplay = document.querySelector('.seconds');
            startTimer(duration, minutesDisplay, secondsDisplay);
        };

        fechaCheckout = function() {
            // Oculta o modal de checkout
            document.getElementById('checkout-inline').classList.add('d-none');

            // Oculta todos os botões de assinatura
            const defaultButton = document.getElementById('default-subscribe-button');
            const subscriptionButtons = document.querySelectorAll('.subscription-button');
            subscriptionButtons.forEach(button => {
                button.classList.add('d-none');
            });
            
            // Garante que o botão padrão seja exibido
            defaultButton.classList.remove('d-none');

            // Exibe novamente a seleção dos planos
            document.querySelector('.subscription-selection.selectPlan').classList.remove('d-none');
            
            // Reseta a seleção dos planos: desmarca todos e marca o default (plan1)
            document.querySelectorAll('input[name=subscription_plan]').forEach(input => {
                input.checked = false;
            });
            document.querySelector('#plan1').checked = true;
        }



        function togglePasswordVisibility(button) {
            const input = button.closest('.input-group').querySelector('input');
            const icon = button.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        $(document).ready(function() {
            $('#inline-register-form').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if(response.success) {
                            window.location.reload();
                        }
                    },
                    error: function(xhr) {
                        if(xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(key => {
                                toastr.error(errors[key][0]);
                            });
                        } else {
                            toastr.error('Ocorreu um erro ao processar seu registro.');
                        }
                    }
                });
            });
        });

        function abrirModal() {
            $('#login-dialog').modal('show');
        }


        function applyCoupon() {
            let CouponField = $('input.form-control[name="coupon"]'); // Campo visível
            let HiddenCouponField = $('#coupon'); // Campo oculto do cupom
            let couponCode = CouponField.val().trim(); // Obtém o valor digitado

            if (couponCode === '') {
                toastr.error('{{ __("Informe um código de cupom") }}');
                return;
            }

            $.ajax({
                url: '{{ route("coupon.validate") }}',
                type: 'POST',
                data: {
                    coupon: couponCode,
                    username: '{{ $user->username }}', // Se necessário para validação no backend
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.valid) {
                        console.log("Cupom validado:", couponCode);
                        console.log("Resposta do servidor:", response);

                        // Atualiza os dados do cupom no objeto de pagamento
                        checkout.paymentData.coupon = response.coupon_code;
                        checkout.paymentData.couponDiscount = response.discount ? response.discount.value : 0;
                        checkout.paymentData.couponDiscountType = response.discount ? response.discount.type : null;

                        // Atualiza os campos ocultos no formulário
                        HiddenCouponField.val(response.coupon_code);
                        $('#coupon_discount').val(checkout.paymentData.couponDiscount);
                        $('#coupon_discount_type').val(checkout.paymentData.couponDiscountType);

                        // Obtém o subtotal diretamente do plano selecionado via atributo data-amount
                        let subtotal = parseFloat($('input[name="subscription_plan"]:checked').data('amount')) || 0;
                        // Verifica se há taxas definidas (ou atribui 0 se não houver)
                        let taxes = Number(checkout.paymentData.taxes) || 0;
                        
                        // Calcula o desconto com base no tipo:
                        let discount = 0;
                        if (checkout.paymentData.couponDiscountType === 'percent') {
                            // Se for desconto percentual, calcula a porcentagem do subtotal.
                            // Ex: Se o desconto for 20, significa 20% de desconto.
                            discount = subtotal * (Number(checkout.paymentData.couponDiscount) / 100);
                        } else if (checkout.paymentData.couponDiscountType === 'fixed') {
                            // Se for desconto fixo, utiliza o valor informado.
                            discount = Number(checkout.paymentData.couponDiscount);
                        }

                        // Atualiza os valores exibidos no resumo de pagamento
                        $('#subtotal-amount').text(`$${subtotal.toFixed(2)}`);
                        $('#discount-amount').text(`-$${discount.toFixed(2)}`);
                        let totalBeforeDiscount = subtotal + taxes;
                        const minTotal = 5;
                        if (totalBeforeDiscount - discount < minTotal) {
                            // Ajusta o desconto para que o total seja igual ao mínimo permitido
                            discount = totalBeforeDiscount - minTotal;
                        }
                        let total = totalBeforeDiscount - discount;
                        $('#total-amount').text(`$${total.toFixed(2)}`);


                        // 3) **FILTRAR OS MÉTODOS DE PAGAMENTO** conforme o cupom
                        let pm = response.payment_method; // “all”, “pix” ou “credit_card”

                        // Remover qualquer marcação de “selecionado” para métodos antigos
                        $('.payment-method').removeClass('selected-payment');

                        if (pm === 'all') {
                            // mostra tudo
                            $('.payment-method').removeClass('d-none');
                        }
                        else if (pm === 'pix') {
                            // esconde todos, mostra apenas PIX-related (credit e suitpay)
                            $('.payment-method').addClass('d-none');
                            $('.credit-payment-method, .suitpay-payment-method').removeClass('d-none');
                        }
                        else if (pm === 'credit_card') {
                            // esconde todos, mostra apenas credit e stripe
                            $('.payment-method').addClass('d-none');
                            $('.credit-payment-method, .stripe-payment-method').removeClass('d-none');
                        }

                        toastr.success('{{ __("Cupom aplicado com sucesso!") }}');
                    } else {
                        toastr.error(response.message || '{{ __("Cupom inválido ou não aplicável.") }}');
                    }
                },
                error: function() {
                    toastr.error('{{ __("Erro ao validar o cupom. Tente novamente mais tarde.") }}');
                }
            });
        }

        function validateCouponField() {
            let CouponField = $('input.form-control[name="coupon"]'); // Campo visível
            let HiddenCouponField = $('#coupon'); // Campo oculto do cupom
            let HiddenDiscountField = $('#coupon_discount'); // Campo oculto do desconto
            let HiddenDiscountTypeField = $('#coupon_discount_type'); // Campo oculto do tipo de desconto

            if (CouponField.length) {
                setTimeout(() => {
                    let couponValue = CouponField.val().trim();
                    checkout.paymentData.coupon = couponValue; 
                    HiddenCouponField.val(couponValue);

                    console.log("Cupom capturado e atualizado no campo oculto:", couponValue);
                    console.log("Desconto capturado e atualizado no campo oculto:", HiddenDiscountField.val());
                    console.log("Tipo capturado e atualizado no campo oculto:", HiddenDiscountTypeField.val());

                }, 100);
            } else {
                console.log("Campo de cupom não encontrado.");
            }
        }

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
    
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    

    <style>
        body {
            padding: 0;
        }

        .flex-fill {
            background: linear-gradient(185deg, rgba(0,0,0,1) 39%, rgba(99,0,0,1) 71%, rgba(150,0,0,0.5736290854232318) 100%);
        }

        footer {
            display: none;
        }

        .checkout-container {
            background: #1a1a1a;
            color: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            max-width: 600px;
            margin: 0 auto;
        }

        .profile-cover {
            position: relative;
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 60px;
        }

        .profile-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info {
            position: relative;
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: -75px;
            padding: 0 1rem;
        }

        .profile-info img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid #1a1a1a;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .profile-info .details {
            margin-left: 15px;
        }

        .details h5 {
            font-size: 1.5rem;
            margin: 0 0 0.25rem;
        }

        .details .text-muted {
            color: #a0a0a0 !important;
            font-size: 0.9rem;
        }

        .benefits-list {
            list-style: none;
            padding: 1rem 0;
            margin: 1.5rem 0;
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
        }

        .benefits-list li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            font-size: 0.95rem;
        }

        .benefits-list i {
            color: red;
            font-size: 1.1rem;
        }

        .subscription-selection .custom-radio {
            padding-bottom: 1.25rem;
            padding-top: 1.25rem;
            padding-left: 2rem;
            padding-right: 0.5rem;
            border: 2px solid #333;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
            background: #242424;
            overflow: hidden;
        }

        .subscription-selection .custom-control-input {
            border-radius: 50%;
            overflow: hidden;
            padding-left: 10px;
        }

        .subscription-selection .custom-control-label {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .subscription-selection .custom-radio:hover {
            border-color: red;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .subscription-selection .custom-control-input:checked ~ .custom-control-label {
            color: red;
        }

        .subscription-selection .custom-control-label {
            font-size: 1rem;
            color: #fff;
        }

        .timer-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: red;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
        }

        .timer-info {
            display: flex;
            align-items: center;
        }

        .timer-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin-right: 10px;
        }

        .timer-countdown {
            display: flex;
            gap: 5px;
        }

        .timer-box {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .btn-subscribe {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            border: none;
            border-radius: 50px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-subscribe:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76,175,80,0.4);
        }
        
        .bg-darker {
            background: #242424;
            border: 1px solid #333;
            color: #fff;
        }

        .input-group .input-group-append .btn.bg-darker {
            background-color: #242424;
            color: #fff;
            border-color: #333;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bg-darker:focus {
            background: #2a2a2a;
            color: #fff;
            border-color: red;
        }

        @media (max-width: 576px) {
            .checkout-container {
                padding: 1.5rem;
                border-radius: 0;
            }
            
            .profile-cover {
                height: 120px;
                margin-bottom: 50px;
            }
            
            .profile-info img {
                width: 80px;
                height: 80px;
            }
            
            .details h5 {
                font-size: 1.2rem;
            }
            
            .btn-subscribe {
                width: 100%;
                padding: 1rem;
            }
        }

    </style>
@stop

@section('content')

    <div class="row justify-content-center mt-5" style="margin: 0; padding: 0; width: 100%;">
        <div class="col-md-4">
        <div class="card-header timer-container">
            <div class="timer-info">
                <div class="timer-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="white" stroke-width="2"/>
                        <line x1="12" y1="6" x2="12" y2="12" stroke="white" stroke-width="2"/>
                        <line x1="12" y1="12" x2="16" y2="14" stroke="white" stroke-width="2"/>
                    </svg>
                </div>
                <span class="timer-text">Oferta por tempo limitado!</span>
            </div>
            <div class="timer-countdown">
                <div class="timer-box">
                    <span class="minutes">15</span>
                    <span class="label">min</span>
                </div>
                <div class="timer-box">
                    <span class="seconds">00</span>
                    <span class="label">seg</span>
                </div>
            </div>
        </div>
            <div class="checkout-container">
                <div class="profile-cover">
                    <img src="{{ $user->cover }}" alt="Capa do perfil">
                </div>

                <div class="profile-info">
                    <img src="{{ $user->avatar }}" alt="Avatar do usuário" style="z-index: 1;">
                    <div class="details" style="padding-top: 25px;">
                        <h5>{{ $user->name }}</h5>
                        <div class="text-muted">
                                    <span>@</span>{{$user->username}}
                        </div>
                    </div>
                </div>

                <ul class="benefits-list">
                    <li><i class="fas fa-check"></i> Acesso ao conteúdo VIP</li>
                    <li><i class="fas fa-check"></i> Acesso ao meu Telegram VIP</li>
                    <li><i class="fas fa-check"></i> Você pode cancelar a qualquer momento</li>
                </ul>

                @if(!Auth::check())
                    <div id="register-module" class="mt-4 mb-4">
                        <h5 class="mb-3">{{ __('Crie sua conta') }}</h5>
                        
                        <form method="POST" action="{{ route('register') }}" id="inline-register-form" class="mb-3">
                            @csrf
                            
                            <div class="form-group mb-3">
                                <label for="name" class="text-muted small">{{ __('Name') }}</label>
                                <input id="name" type="text" class="form-control bg-darker @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" autocomplete="name" autofocus>
                                @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="email" class="text-muted small">{{ __('E-Mail Address') }}</label>
                                <input id="email" type="email" class="form-control bg-darker @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                                @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="password" class="text-muted small">{{ __('Password') }}</label>
                                <div class="input-group">
                                    <input id="password" type="password" class="form-control bg-darker @error('password') is-invalid @enderror" required name="password" autocomplete="new-password">
                                    <div class="input-group-append">
                                        <button class="btn bg-darker border-0" type="button" onclick="togglePasswordVisibility(this)" style="height: 100%;">
                                            <i class="fas fa-eye text-muted"></i>
                                        </button>
                                    </div>
                                </div>
                                @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="password-confirm" class="text-muted small">{{ __('Confirm Password') }}</label>
                                <input id="password-confirm" type="password" class="form-control bg-darker @error('password_confirmation') is-invalid @enderror" required name="password_confirmation" autocomplete="new-password">
                                @error('password_confirmation')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input @error('terms') is-invalid @enderror" id="tosAgree" type="checkbox" name="terms" value="1">
                                    <label class="custom-control-label small" for="tosAgree">
                                        <span>{{ __('I agree to the') }} <a href="{{route('pages.get',['slug'=>GenericHelper::getTOSPage()->slug])}}">{{ __('Terms of Use') }}</a> {{ __('and') }} <a href="{{route('pages.get',['slug'=>GenericHelper::getPrivacyPage()->slug])}}">{{ __('Privacy Policy') }}</a>.</span>
                                    </label>
                                    @error('terms')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            @if(getSetting('security.recaptcha_enabled') && !Auth::check())
                                <div class="form-group row d-flex justify-content-center captcha-field">
                                    {!! NoCaptcha::display(['data-theme' => (Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme')) : Cookie::get('app_theme') )]) !!}
                                    @error('g-recaptcha-response')
                                    <span class="text-danger" role="alert">
                                        <strong>Verifique o campo captcha.</strong>
                                    </span>
                                    @enderror
                                </div>
                            @endif

                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                {{ __('Register') }}
                            </button>


                        </form>
                    </div>
                    <div class="text-center">
                        <p class="mb-4">
                            {{__('Already got an account?')}}
                            <a href="javascript:void(0);" onclick="abrirModal()" class="text-primary text-gradient font-weight-bold" data-toggle="modal" data-target="#login-dialog">{{__('Sign in')}}</a>
                            @include('elements.modal-login')
                        </p>
                    </div>
                    <hr>
                @endif

                <div class="subscription-selection mt-3 selectPlan">
                    <div class="form-group mb-4">
                        <h5 class="mb-3">{{ __('Escolha seu plano') }}</h5>
                        
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="plan1" name="subscription_plan" class="custom-control-input" 
                                   value="one-month-subscription" 
                                   data-amount="{{ $user->profile_access_price }}" checked>
                            <label class="custom-control-label d-flex justify-content-between" for="plan1">
                                <span>{{ __('1 mês') }}</span>
                                <span>{{ \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($user->profile_access_price) }}</span>
                            </label>
                        </div>

                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="plan3" name="subscription_plan" class="custom-control-input" 
                                   value="three-months-subscription" 
                                   data-amount="{{ $user->profile_access_price_3_months * 3 }}">
                            <label class="custom-control-label d-flex justify-content-between" for="plan3">
                                <span>{{ __('3 meses') }}</span>
                                <span>{{ \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($user->profile_access_price_3_months * 3) }}</span>
                            </label>
                        </div>
                        
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="plan6" name="subscription_plan" class="custom-control-input" 
                                   value="six-months-subscription" 
                                   data-amount="{{ $user->profile_access_price_6_months * 6 }}">
                            <label class="custom-control-label d-flex justify-content-between" for="plan6">
                                <span>{{ __('6 meses') }}</span>
                                <span>{{ \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($user->profile_access_price_6_months * 6) }}</span>
                            </label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="plan12" name="subscription_plan" class="custom-control-input" 
                                   value="yearly-subscription" 
                                   data-amount="{{ $user->profile_access_price_12_months * 12 }}">
                            <label class="custom-control-label d-flex justify-content-between" for="plan12">
                                <span>{{ __('12 meses') }}</span>
                                <span>{{ \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($user->profile_access_price_12_months * 12) }}</span>
                            </label>
                        </div>
                    </div>
                    <!-- Botão de inscrição -->
                    <div id="default-subscribe-button">
                        @include('elements.checkout.subscribe-button-30')
                    </div>
                    @if($user->profile_access_price_3_months)
                        <div id="subscribe-button-90" class="subscription-button d-none">
                            @include('elements.checkout.subscribe-button-90')
                        </div>
                    @endif
                    @if($user->profile_access_price_6_months)
                        <div id="subscribe-button-182" class="subscription-button d-none">
                            @include('elements.checkout.subscribe-button-182')
                        </div>
                    @endif
                    @if($user->profile_access_price_12_months)
                        <div id="subscribe-button-365" class="subscription-button d-none">
                            @include('elements.checkout.subscribe-button-365')
                        </div>
                    @endif
                </div>
                
                <div class="row">
                    <div class="d-none" id="checkout-inline">
                        <div class="position-relative">
                                    <!-- Botão X vermelho para fechar o modal -->
                            <button type="button" class="close text-danger" aria-label="Close" style="position: absolute; top: 10px; right: 10px; z-index: 10;" onclick="fechaCheckout()">
                                <span aria-hidden="true">&times;</span>
                            </button>
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
                                    <input type="hidden" name="coupon" id="coupon" value="">

                                    <input type="hidden" name="coupon_discount" id="coupon_discount" value="">
                                    <input type="hidden" name="coupon_discount_type" id="coupon_discount_type" value="">

                                    <button class="payment-button" type="submit"></button>
                                </form>
                            </div>
                            <h5 class="mb-3">{{ __('Finalize sua compra') }}</h5>
                            <div class="card-body">
                                <div class="payment-body">
                                    <div class="payment-description mb-3 d-none"></div>
                                    <div class="input-group mb-3 checkout-amount-input d-none">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="amount-label">
                                                @include('elements.icon', ['icon' => 'cash-outline', 'variant' => 'medium', 'centered' => false])
                                            </span>
                                        </div>
                                        <input class="form-control uifield-amount" placeholder="{{ __( \App\Providers\SettingsServiceProvider::leftAlignedCurrencyPosition() ? 'Amount ($5 min, $500 max)' : 'Amount (5$ min, 500$ max)', ['min'=> getSetting('payments.min_tip_value'), 'max'=> getSetting('payments.max_tip_value'), 'currency'=> config('app.site.currency_symbol')]) }}" aria-label="Amount" aria-describedby="amount-label" id="checkout-amount" type="number" min="0" step="1" max="500">
                                        <div class="invalid-feedback">{{ __('Please enter a valid amount.') }}</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="card">
                                        <div id="billingInformation" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                                            <div class="card-body" style="background: rgba(0, 0, 0, 0.89);">
                                                <form id="billing-agreement-form">
                                                <div class="tab-content">
                                                    <div id="individual" class="tab-pane show active pt-1">
                                                        <div class="row form-group">
                                                            <div class="col-sm-6 col-6">
                                                                <div class="form-group">
                                                                    <label for="firstName"><span>{{ __('First name') }}</span></label>
                                                                    <input type="text" name="firstName" placeholder="{{ __('First name') }}" onchange="checkout.validateFirstNameField();" required class="form-control uifield-first_name">
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6 col-6">
                                                                <div class="form-group">
                                                                    <label for="lastName"><span>{{ __('Last name') }}</span></label>
                                                                    <input type="text" name="lastName" placeholder="{{ __('Last name') }}" onblur="checkout.validateLastNameField()" required class="form-control uifield-last_name">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="countrySelect"><span>{{ __('Country') }}</span></label>
                                                            <select class="country-select form-control input-sm uifield-country" id="countrySelect" required onchange="checkout.validateCountryField()"></select>
                                                        </div>

                                                        <div class="row form-group">
                                                            <div class="col-sm-6 col-6">
                                                                <div class="form-group">
                                                                    <label for="paymentPhone">
                                                                        <span>{{ __('Phone') }}</span>
                                                                    </label>
                                                                    <input
                                                                        type="tel"
                                                                        id="paymentPhone"
                                                                        name="phone"
                                                                        class="form-control uifield-phone"
                                                                        placeholder="(00) 00000-0000"
                                                                        required
                                                                        oninput="validatePhoneField();"
                                                                    >
                                                                    <div class="valid-feedback">
                                                                        Número Válido!
                                                                    </div>
                                                                    <div class="invalid-feedback" id="phone-error">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>




                                                    </div>
                                                    <div class="billing-agreement-error error text-danger d-none">{{ __('Please complete all billing details') }}</div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">{{ __('Código do Cupom') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="input-group">
                                        <!-- Ícone -->
                                        <div class="input-group-prepend">
                                            <span class="input-group-text d-flex align-items-center" id="coupon-label">
                                                @include('elements.icon', ['icon' => 'ticket-outline', 'variant' => 'medium', 'centered' => false])
                                            </span>
                                        </div>
                                        
                                        <!-- Campo de entrada -->
                                        <input 
                                            value="{{ isset($coupon) ? $coupon->coupon_code : '' }}" 
                                            type="text" 
                                            class="form-control" 
                                            name="coupon" 
                                            id="coupon-input" 
                                            placeholder="{{ __('Digite o código do cupom') }}" 
                                            aria-label="Coupon" 
                                            aria-describedby="coupon-label">
                                        
                                        <!-- Botão -->
                                        <div class="input-group-append">
                                            <button class="btn btn-danger apply-btn-fix" type="button" id="apply-coupon-btn" onclick="applyCoupon()">
                                                {{ __('Aplicar') }}
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Alerta sutil em vermelho -->
                                    <small class="form-text text-muted">
                                        Clique em <span class="text-danger">"APLICAR"</span> para validar o cupom.
                                    </small>
                                </div>
                            </div>

                            <style>
                            /* Garante que todos os elementos dentro do input-group tenham a mesma altura */
                            .input-group .form-control {
                                height: auto; /* Ajusta automaticamente conforme necessário */
                            }

                            /* Ajusta o botão para ocupar a mesma altura do input */
                            .apply-btn-fix {
                                height: 100%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                padding: 0 15px; /* Mantém um espaçamento adequado */
                            }
                            </style>


                            <div class="mb-3">
                                <h6>{{ __('Payment summary') }}</h6>
                                <div class="subtotal row">
                                    <span class="col-sm left"><b>{{ __('Subtotal') }}:</b></span>
                                    <span class="subtotal-amount col-sm right text-right">
                                        <b id="subtotal-amount">$0.00</b>
                                    </span>
                                </div>
                                <div class="discount row">
                                    <span class="col-sm left"><b>{{ __('Desconto') }}:</b></span>
                                    <span class="discount-amount col-sm right text-right">
                                        <b id="discount-amount">$0.00</b>
                                    </span>
                                </div>
                                <div class="taxes row">
                                    <span class="col-sm left"><b>{{ __('Taxes') }}</b></span>
                                    <span class="taxes-amount col-sm right text-right">
                                        <b id="taxes-amount">$0.00</b>
                                    </span>
                                </div>
                                <div class="taxes-details"></div>
                                <div class="total row">
                                    <span class="col-sm left"><b>{{ __('Total') }}:</b></span>
                                    <span class="total-amount col-sm right text-right">
                                        <b id="total-amount">$0.00</b>
                                    </span>
                                </div>

                                        <div style="margin-top: 5px;">
                                            <h6>{{__('Payment method')}}</h6>
                                            <div class="d-flex text-left radio-group row px-2">
                                                @if(getSetting('payments.stripe_secret_key') && getSetting('payments.stripe_public_key') && !getSetting('payments.stripe_checkout_disabled'))
                                                    <div class="p-1 col-6 col-md-3 col-lg-3 stripe-payment-method payment-method" data-value="stripe">
                                                        <div class="radio mx-auto stripe-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center">
                                                            <img src="{{asset('/img/logos/stripe.svg')}}">
                                                        </div>
                                                    </div>
                                                @endif
                                                @if(config('paypal.client_id') && config('paypal.secret') && !getSetting('payments.paypal_checkout_disabled'))
                                                    <div class="p-1 col-6 col-md-3 col-lg-3 paypal-payment-method payment-method" data-value="paypal">
                                                        <div class="radio mx-auto paypal-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center">
                                                            <img src="{{asset('/img/logos/paypal.svg')}}">
                                                        </div>
                                                    </div>
                                                @endif
                                                @if(getSetting('payments.coinbase_api_key') && !getSetting('payments.coinbase_checkout_disabled'))
                                                    <div class="p-1 col-6 col-md-3 col-lg-3 d-none coinbase-payment-method payment-method" data-value="coinbase">
                                                        <div class="radio mx-auto coinbase-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center">
                                                            <img src="{{asset('/img/logos/coinbase.svg')}}">
                                                        </div>
                                                    </div>
                                                @endif
{{-- @if(getSetting('payments.nowpayments_api_key') && !getSetting('payments.nowpayments_checkout_disabled'))
    <div class="p-1 col-6 col-md-3 col-lg-3 d-none nowpayments-payment-method payment-method" data-value="nowpayments">
        <div class="radio mx-auto nowpayments-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center">
            <img src="{{ asset('/img/logos/nowpayments.svg') }}">
        </div>
    </div>
@endif --}}
                                                @if(getSetting('payments.mercado_access_token') && !getSetting('payments.mercado_checkout_disabled'))
                                                    <div class="p-1 col-6 col-md-3 d-none mercado-payment-method payment-method" data-value="mercado">
                                                        <div class="radio mx-auto mercado-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center">
                                                            <img src="{{asset('/img/logos/mercado.svg')}}">
                                                        </div>
                                                    </div>
                                                @endif
                                                @if (config('services.suitpay.enabled') && !getSetting('payments.nowpayments_checkout_disabled'))
                                                    <div class="p-1 col-6 col-md-3 d-none suitpay-payment-method payment-method" data-value="suitpay">
                                                        <div class="radio mx-auto suitpay-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center">
                                                            <img src="{{asset('/img/logos/pix.png')}}">
                                                        </div>
                                                    </div>
                                                @endif
                                                @if(getSetting('payments.pagarme_secret_key') && getSetting('payments.pagarme_public_key'))
                                                    <div class="p-1 col-6 col-md-3 stripe-pix-payment-method payment-method" data-value="stripe_pix">
                                                        <div class="radio mx-auto stripe-pix-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center">
                                                            <img src="{{asset('/img/logos/pix.png')}}">
                                                        </div>
                                                    </div>
                                                @endif
                                                <div class="credit-payment-method p-1 col-6 col-md-3 col-lg-3 payment-method" data-value="credit">
                                                    <div class="radio mx-auto credit-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center">
                                                        <div class="credit-provider-text">
                                                            <b>{{__("Credit")}}</b>
                                                            <div class="available-credit">({{\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount('0')}})</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <style>
                                            .radio-group {
                                                display: flex;
                                                flex-wrap: wrap;
                                                justify-content: center; /* Centraliza os botões */
                                                gap: 10px; /* Espaço entre os botões */
                                            }

                                            .payment-method {
                                                cursor: pointer;
                                                transition: all 0.3s ease-in-out;
                                                border-radius: 8px;
                                                padding: 10px;
                                                border: 2px solid #dc3545; /* Borda vermelha */
                                                background-color: transparent; /* Sem fundo por padrão */
                                                flex: 1 1 auto; /* Permite ajuste responsivo */
                                                max-width: 150px; /* Define um tamanho máximo para manter o layout */
                                                text-align: center;
                                            }

                                            .payment-method:hover {
                                                background-color: rgba(220, 53, 69, 0.1); /* Leve destaque ao passar o mouse */
                                            }

                                            .selected-payment {
                                                border: 2px solid #dc3545; /* Mantém a borda vermelha */
                                                box-shadow: 0px 0px 10px rgba(220, 53, 69, 0.5); /* Efeito de brilho vermelho */
                                                background-color: rgba(220, 53, 69, 0.2); /* Fundo vermelho apenas quando selecionado */
                                            }

                                            .checkout-payment-provider img {
                                                max-width: 80%;
                                                height: auto;
                                            }


                                        </style>

                                        <script>
                                            document.addEventListener("DOMContentLoaded", function () {
                                                const paymentMethods = document.querySelectorAll(".payment-method");

                                                paymentMethods.forEach(method => {
                                                    method.addEventListener("click", function () {
                                                        paymentMethods.forEach(m => m.classList.remove("selected-payment"));
                                                        this.classList.add("selected-payment");
                                                    });
                                                });
                                            });

                                            function validatePhoneField() {
                                                const phoneField = document.querySelector('input[name="phone"]');
                                                const rawPhoneVal = phoneField.value;
                                                const onlyDigits = rawPhoneVal.replace(/\D/g, '');

                                                // Se o campo estiver vazio, removemos qualquer feedback e saímos
                                                if (rawPhoneVal.trim() === '') {
                                                    phoneField.classList.remove('is-invalid', 'is-valid');
                                                    document.getElementById('phone-error').textContent = '';
                                                    // Também zera o dado no objeto checkout, caso exista
                                                    if (window.checkout && checkout.paymentData) {
                                                        checkout.paymentData.phone = '';
                                                    }
                                                    return;
                                                }

                                                // Se não estiver vazio, validamos a quantidade de dígitos
                                                if (onlyDigits.length !== 11) {
                                                    phoneField.classList.add('is-invalid');
                                                    phoneField.classList.remove('is-valid');
                                                    document.getElementById('phone-error').textContent = 'Por favor, insira um número de celular válido com DDD.';
                                                    if (window.checkout && checkout.paymentData) {
                                                        checkout.paymentData.phone = '';
                                                    }
                                                } else {
                                                    phoneField.classList.remove('is-invalid');
                                                    phoneField.classList.add('is-valid');
                                                    document.getElementById('phone-error').textContent = '';
                                                    if (window.checkout && checkout.paymentData) {
                                                        checkout.paymentData.phone = rawPhoneVal;
                                                    }
                                                }
                                            }

                                        </script>

                                        
                                        <div class="payment-error error text-danger text-bold d-none mb-1">{{__('Please select your payment method')}}</div>
                                        <p class="text-muted mt-1"> {{__('Note: After clicking on the button, you will be directed to a secure gateway for payment. After completing the payment process, you will be redirected back to the website.')}} </p>
                                    </div>
                                    <div class="modal-footer">
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
@endsection









            