/**
 * Component used for handling checkout dialog actions
 */
"use strict";
/* global app, trans, trans_choice, launchToast, getWebsiteFormattedAmount */


function openCheckoutInline(button) {
    // Oculta o botão de inscrição após o clique
    button.classList.add("d-none");
    // Exibe o container do checkout integrado removendo a classe "d-none"
    document.getElementById('checkout-inline').classList.remove("d-none");
    
    // Oculta a div de seleção de plano
    document.querySelector('.subscription-selection.selectPlan').classList.add("d-none");

    // Chamada dos pixels de checkout ou outras ações de rastreamento, se necessárias
    send_initial_checkout_pixels();




    // Obtém os dados armazenados nos atributos data- do botão
    var $button = $(button);
    var postId = $button.data('post-id');
    var recipientId = $button.data('recipient-id');
    var amount = $button.data('amount');
    var type = $button.data('type');
    var username = $button.data('username');
    var firstName = $button.data('first-name');
    var lastName = $button.data('last-name');
    var billingAddress = $button.data('billing-address');
    var name = $button.data('name');
    var avatar = $button.data('avatar');
    var country = $button.data('country');
    var city = $button.data('city');
    var state = $button.data('state');
    var postcode = $button.data('postcode');
    var availableCredit = $button.data('available-credit');
    var streamId = $button.data('stream-id');
    var userMessageId = $button.data('message-id');
    var coupon = $button.data('coupon');

    var couponDiscount = $button.data('coupon_discount') || null;
    var couponDiscountType = $button.data('coupon_discount_type') || null;
    
    
    // Inicia os dados de pagamento
    checkout.initiatePaymentData(
        type,
        amount,
        postId,
        recipientId,
        firstName,
        lastName,
        billingAddress,
        country,
        city,
        state,
        postcode,
        availableCredit,
        streamId,
        userMessageId,
        coupon,
        couponDiscount,
        couponDiscountType
        
    );
    
    checkout.updateUserDetails(avatar, username, name);
    checkout.fillCountrySelectOptions();
    checkout.updatePaymentSummaryData();
    checkout.prefillBillingDetails();

    // Define título e descrição do pagamento conforme o tipo
    let paymentTitle = '';
    let paymentDescription = '';
    if (type === 'tip' || type === 'chat-tip') {
        $('.payment-body .checkout-amount-input').removeClass('d-none');
        paymentTitle = trans("Send a tip");
        paymentDescription = trans("Send a tip to this user");
        checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
    } else if (
        type === 'one-month-subscription' ||
        type === 'three-months-subscription' ||
        type === 'six-months-subscription' ||
        type === 'yearly-subscription'
    ) {
        let numberOfMonths = 1;
        let showStripeProvider = !app.stripeRecurringDisabled;
        let showPaypalProvider = !app.paypalRecurringDisabled;
        let showCCBillProvider = !app.ccBillRecurringDisabled;
        let showCreditProvider = !app.localWalletRecurringDisabled;
        let showNoxpayProvider = !app.noxpayRecurringDisabled;

        // Ajusta para assinaturas com duração diferente
        if (showCCBillProvider) {
            if (type === 'three-months-subscription') {
                numberOfMonths = 3;
            } else if (type === 'six-months-subscription') {
                numberOfMonths = 6;
                showCCBillProvider = false;
            } else if (type === 'yearly-subscription') {
                numberOfMonths = 12;
                showCCBillProvider = false;
            }
        }

        checkout.togglePaymentProviders(false, checkout.oneTimePaymentProcessorClasses);
        checkout.togglePaymentProvider(showCCBillProvider, '.ccbill-payment-method');
        checkout.togglePaymentProvider(showStripeProvider, '.stripe-payment-method');
        checkout.togglePaymentProvider(showPaypalProvider, '.paypal-payment-method');
        checkout.togglePaymentProvider(showPaypalProvider, '.suitpay-payment-method');
        checkout.togglePaymentProvider(showNoxpayProvider, '.noxpay-payment-method');
        checkout.togglePaymentProvider(showCreditProvider, '.credit-payment-method');

        $('.payment-body .checkout-amount-input').addClass('d-none');
        paymentTitle = trans(type);
        let subscriptionInterval = trans_choice('months', numberOfMonths, {'number': numberOfMonths});
        let key = app.currencyPosition === 'left' ? 'Subscribe to' : 'Subscribe to rightAligned';
        paymentDescription = trans(key, {
            'amount': amount,
            'currency': app.currencySymbol,
            'username': name,
            'subscription_interval': subscriptionInterval
        });
        checkout.toggleCryptoPaymentProviders(false);
    } else if (type === 'post-unlock') {
        $('.payment-body .checkout-amount-input').addClass('d-none');
        paymentTitle = trans('Unlock post');
        paymentDescription = trans('Unlock post for') + ' ' + getWebsiteFormattedAmount(amount);
        checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
    } else if (type === 'stream-access') {
        $('.payment-body .checkout-amount-input').addClass('d-none');
        paymentTitle = trans('Join streaming');
        paymentDescription = trans('Join streaming now for') + ' ' + getWebsiteFormattedAmount(amount);
        checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
    } else if (type === 'message-unlock') {
        $('.payment-body .checkout-amount-input').addClass('d-none');
        paymentTitle = trans('Unlock message');
        paymentDescription = trans('Unlock message for') + ' ' + getWebsiteFormattedAmount(amount);
        checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
    }

    if (paymentTitle !== '' || paymentDescription !== '') {
        $('#payment-title').text(paymentTitle);
        $('.payment-body .payment-description').removeClass('d-none');
        $('.payment-body .payment-description').text(paymentDescription);
    }

    // Exibe ou oculta as informações de faturamento
    if (!firstName || !lastName || !billingAddress || !city || !state || !postcode || !country) {
        $('#billingInformation').collapse('show');
    } else {
        $('#billingInformation').collapse('hide');
    }
    $('#checkout-amount').val(amount);
}

$(function () {
    // Document ready
    // Deposit amount change event listener
    $('#checkout-amount').on('change', function () {
        if (!checkout.checkoutAmountValidation()) {
            return false;
        }

        // update payment amount
        checkout.paymentData.amount = parseFloat($('#checkout-amount').val());
        checkout.updatePaymentSummaryData();
    });

    // Checkout proceed button event listener
    $('.checkout-continue-btn').on('click', function () {
        checkout.activePaymentButton = $(this);
        checkout.initPayment();
    });

    $('.checkout-noxpay-btn').on('click', function () {
        const $button = $(this);
        checkout.activePaymentButton = $button;
        const $noxpayMethod = $('.noxpay-payment-method');
        if ($noxpayMethod.length && $noxpayMethod.hasClass('d-none')) {
            $noxpayMethod.removeClass('d-none');
        }
        const $noxpayProvider = $('.noxpay-payment-provider');
        if ($noxpayProvider.length) {
            $noxpayProvider.trigger('click');
            const $noxpayContainer = $noxpayProvider.closest('.payment-method');
            if ($noxpayContainer.length) {
                $('.payment-method').removeClass('selected-payment');
                $noxpayContainer.addClass('selected-payment');
            }
            checkout.syncNoxpayButton();
            checkout.initPayment();
        }
    });

    $('.custom-control').on('change', function () {
        $('.error-message').hide();
    });

    $('#headingOne').on('click', function () {
        if ($('#headingOne').hasClass('collapsed')) {
            $('.card-header .label-icon').html('<ion-icon name="chevron-up-outline"></ion-icon>');
        } else {
            $('.card-header .label-icon').html('<ion-icon name="chevron-down-outline"></ion-icon>');
        }
    });

    $('#checkout-center').on('show.bs.modal', function (e) {
        //get data-id attribute of the clicked element
        var postId = $(e.relatedTarget).data('post-id');
        var recipientId = $(e.relatedTarget).data('recipient-id');
        var amount = $(e.relatedTarget).data('amount');
        var type = $(e.relatedTarget).data('type');
        var username = $(e.relatedTarget).data('username');
        var firstName = $(e.relatedTarget).data('first-name');
        var lastName = $(e.relatedTarget).data('last-name');
        var billingAddress = $(e.relatedTarget).data('billing-address');
        var name = $(e.relatedTarget).data('name');
        var avatar = $(e.relatedTarget).data('avatar');
        var country = $(e.relatedTarget).data('country');
        var city = $(e.relatedTarget).data('city');
        var state = $(e.relatedTarget).data('state');
        var postcode = $(e.relatedTarget).data('postcode');
        var availableCredit = $(e.relatedTarget).data('available-credit');
        var streamId = $(e.relatedTarget).data('stream-id');
        var userMessageId = $(e.relatedTarget).data('message-id');
        var coupon = $(e.relatedTarget).data('coupon')

        var couponDiscount = $(e.relatedTarget).data('coupon_discount') || null;
        var couponDiscountType = $(e.relatedTarget).data('coupon_discount_type') || null;
    
        checkout.initiatePaymentData(
            type,
            amount,
            postId,
            recipientId,
            firstName,
            lastName,
            billingAddress,
            country,
            city,
            state,
            postcode,
            availableCredit,
            streamId,
            userMessageId,
            coupon,
            couponDiscount,
            couponDiscountType
        );
        checkout.updateUserDetails(avatar, username, name);
        checkout.fillCountrySelectOptions();
        checkout.updatePaymentSummaryData();
        checkout.prefillBillingDetails();

        let paymentTitle = '';
        let paymentDescription = '';
        if (type === 'tip' || type === 'chat-tip') {
            $('.payment-body .checkout-amount-input').removeClass('d-none');
            paymentTitle = trans("Send a tip");
            paymentDescription = trans("Send a tip to this user");
            checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
        } else if (type === 'one-month-subscription'
            || type === 'three-months-subscription'
            || type === 'six-months-subscription'
            || type === 'yearly-subscription') {
            let numberOfMonths = 1;
            let showStripeProvider = !app.stripeRecurringDisabled;
            let showPaypalProvider = !app.paypalRecurringDisabled;
            let showCCBillProvider = !app.ccBillRecurringDisabled;
            let showCreditProvider = !app.localWalletRecurringDisabled;
            let showNoxpayProvider = !app.noxpayRecurringDisabled;

            // handles ccbill provider as they only allow 30 or 90 days subscriptions
            if(showCCBillProvider){
                if (type === 'three-months-subscription') {
                    numberOfMonths = 3;
                } else if (type === 'six-months-subscription') {
                    numberOfMonths = 6;
                    showCCBillProvider = false;
                } else if (type === 'yearly-subscription') {
                    numberOfMonths = 12;
                    showCCBillProvider = false;
                }
            }

            checkout.togglePaymentProviders(false, checkout.oneTimePaymentProcessorClasses);
            checkout.togglePaymentProvider(showCCBillProvider, '.ccbill-payment-method');
            checkout.togglePaymentProvider(showStripeProvider, '.stripe-payment-method');
            checkout.togglePaymentProvider(showPaypalProvider, '.paypal-payment-method');
            checkout.togglePaymentProvider(showPaypalProvider, '.suitpay-payment-method');
            checkout.togglePaymentProvider(showNoxpayProvider, '.noxpay-payment-method');
            checkout.togglePaymentProvider(showCreditProvider, '.credit-payment-method');

            $('.payment-body .checkout-amount-input').addClass('d-none');
            paymentTitle = trans(type);
            let subscriptionInterval = trans_choice('months', numberOfMonths, {'number': numberOfMonths});
            let key = app.currencyPosition === 'left' ? 'Subscribe to' : 'Subscribe to rightAligned';
            paymentDescription = trans(key, {
                'amount': amount,
                'currency': app.currencySymbol,
                'username': name,
                'subscription_interval': subscriptionInterval
            });
            checkout.toggleCryptoPaymentProviders(false);
        } else if (type === 'post-unlock') {
            $('.payment-body .checkout-amount-input').addClass('d-none');
            paymentTitle = trans('Unlock post');
            paymentDescription = trans('Unlock post for') + ' ' + getWebsiteFormattedAmount(amount);
            checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
        } else if (type === 'stream-access') {
            $('.payment-body .checkout-amount-input').addClass('d-none');
            paymentTitle = trans('Join streaming');
            paymentDescription = trans('Join streaming now for') + ' ' + getWebsiteFormattedAmount(amount);
            checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
        } else if (type === 'message-unlock') {
            $('.payment-body .checkout-amount-input').addClass('d-none');
            paymentTitle = trans('Unlock message');
            paymentDescription = trans('Unlock message for') + ' ' + getWebsiteFormattedAmount(amount);
            checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
        }

        if (paymentTitle !== '' || paymentDescription !== '') {
            $('#payment-title').text(paymentTitle);
            $('.payment-body .payment-description').removeClass('d-none');
            $('.payment-body .payment-description').text(paymentDescription);
        }

        if (!firstName || !lastName || !billingAddress || !city || !state || !postcode || !country) {
            $('#billingInformation').collapse('show');
        } else {
            $('#billingInformation').collapse('hide');
        }
        $('#checkout-amount').val(amount);
    });

    $('#checkout-center').on('hidden.bs.modal', function () {
        $(this).find('#billing-agreement-form').trigger('reset');
        $('.payment-error').addClass('d-none');
    });












    $('#checkout-drop').on('show.bs.dropdown', function (e) {
        //get data-id attribute of the clicked element
        var postId = $(e.relatedTarget).data('post-id');
        var recipientId = $(e.relatedTarget).data('recipient-id');
        var amount = $(e.relatedTarget).data('amount');
        var type = $(e.relatedTarget).data('type');
        var username = $(e.relatedTarget).data('username');
        var firstName = $(e.relatedTarget).data('first-name');
        var lastName = $(e.relatedTarget).data('last-name');
        var billingAddress = $(e.relatedTarget).data('billing-address');
        var name = $(e.relatedTarget).data('name');
        var avatar = $(e.relatedTarget).data('avatar');
        var country = $(e.relatedTarget).data('country');
        var city = $(e.relatedTarget).data('city');
        var state = $(e.relatedTarget).data('state');
        var postcode = $(e.relatedTarget).data('postcode');
        var availableCredit = $(e.relatedTarget).data('available-credit');
        var streamId = $(e.relatedTarget).data('stream-id');
        var userMessageId = $(e.relatedTarget).data('message-id');
        var coupon = $(e.relatedTarget).data('coupon')

        var couponDiscount = $(e.relatedTarget).data('coupon_discount') || null;
        var couponDiscountType = $(e.relatedTarget).data('coupon_discount_type') || null;
    

        checkout.initiatePaymentData(
            type,
            amount,
            postId,
            recipientId,
            firstName,
            lastName,
            billingAddress,
            country,
            city,
            state,
            postcode,
            availableCredit,
            streamId,
            userMessageId,
            coupon,
            couponDiscount,
            couponDiscountType
        );
        checkout.updateUserDetails(avatar, username, name);
        checkout.fillCountrySelectOptions();
        checkout.updatePaymentSummaryData();
        checkout.prefillBillingDetails();

        let paymentTitle = '';
        let paymentDescription = '';
        if (type === 'tip' || type === 'chat-tip') {
            $('.payment-body .checkout-amount-input').removeClass('d-none');
            paymentTitle = trans("Send a tip");
            paymentDescription = trans("Send a tip to this user");
            checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
        } else if (type === 'one-month-subscription'
            || type === 'three-months-subscription'
            || type === 'six-months-subscription'
            || type === 'yearly-subscription') {
            let numberOfMonths = 1;
            let showStripeProvider = !app.stripeRecurringDisabled;
            let showPaypalProvider = !app.paypalRecurringDisabled;
            let showCCBillProvider = !app.ccBillRecurringDisabled;
            let showCreditProvider = !app.localWalletRecurringDisabled;

            // handles ccbill provider as they only allow 30 or 90 days subscriptions
            if(showCCBillProvider){
                if (type === 'three-months-subscription') {
                    numberOfMonths = 3;
                } else if (type === 'six-months-subscription') {
                    numberOfMonths = 6;
                    showCCBillProvider = false;
                } else if (type === 'yearly-subscription') {
                    numberOfMonths = 12;
                    showCCBillProvider = false;
                }
            }

            checkout.togglePaymentProviders(false, checkout.oneTimePaymentProcessorClasses);
            checkout.togglePaymentProvider(showCCBillProvider, '.ccbill-payment-method');
            checkout.togglePaymentProvider(showStripeProvider, '.stripe-payment-method');
            checkout.togglePaymentProvider(showPaypalProvider, '.paypal-payment-method');
            checkout.togglePaymentProvider(showPaypalProvider, '.suitpay-payment-method');
            checkout.togglePaymentProvider(showNoxpayProvider, '.noxpay-payment-method');
            checkout.togglePaymentProvider(showCreditProvider, '.credit-payment-method');

            $('.payment-body .checkout-amount-input').addClass('d-none');
            paymentTitle = trans(type);
            let subscriptionInterval = trans_choice('months', numberOfMonths, {'number': numberOfMonths});
            let key = app.currencyPosition === 'left' ? 'Subscribe to' : 'Subscribe to rightAligned';
            paymentDescription = trans(key, {
                'amount': amount,
                'currency': app.currencySymbol,
                'username': name,
                'subscription_interval': subscriptionInterval
            });
            checkout.toggleCryptoPaymentProviders(false);
        } else if (type === 'post-unlock') {
            $('.payment-body .checkout-amount-input').addClass('d-none');
            paymentTitle = trans('Unlock post');
            paymentDescription = trans('Unlock post for') + ' ' + getWebsiteFormattedAmount(amount);
            checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
        } else if (type === 'stream-access') {
            $('.payment-body .checkout-amount-input').addClass('d-none');
            paymentTitle = trans('Join streaming');
            paymentDescription = trans('Join streaming now for') + ' ' + getWebsiteFormattedAmount(amount);
            checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
        } else if (type === 'message-unlock') {
            $('.payment-body .checkout-amount-input').addClass('d-none');
            paymentTitle = trans('Unlock message');
            paymentDescription = trans('Unlock message for') + ' ' + getWebsiteFormattedAmount(amount);
            checkout.togglePaymentProviders(true, checkout.oneTimePaymentProcessorClasses);
        }

        if (paymentTitle !== '' || paymentDescription !== '') {
            $('#payment-title').text(paymentTitle);
            $('.payment-body .payment-description').removeClass('d-none');
            $('.payment-body .payment-description').text(paymentDescription);
        }

        if (!firstName || !lastName || !billingAddress || !city || !state || !postcode || !country) {
            $('#billingInformation').collapse('show');
        } else {
            $('#billingInformation').collapse('hide');
        }
        $('#checkout-amount').val(amount);
    });

    $('#checkout-drop').on('hidden.bs.dropdown', function () {
        $(this).find('#billing-agreement-form').trigger('reset');
        $('.payment-error').addClass('d-none');
    });













    
    // Radio button
    $('.radio-group .radio').on('click', function () {
        $(this).parent().parent().find('.radio').removeClass('selected');
        $(this).addClass('selected');
        $('.payment-error').addClass('d-none');
    });

    $('.country-select').on('change', function () {
        checkout.updatePaymentSummaryData();
    });

    checkout.syncNoxpayButton();
});

/**
 * Checkout class
 */
var checkout = {
    allowedPaymentProcessors: ['stripe', 'paypal', 'credit', 'coinbase', 'nowpayments', 'ccbill', 'paystack', 'oxxo', 'mercado', 'suitpay', 'noxpay'],
    paymentData: {},
    activePaymentButton: null,
    oneTimePaymentProcessorClasses: [
        '.nowpayments-payment-method',
        '.coinbase-payment-method',
        '.ccbill-payment-method',
        '.stripe-payment-method',
        '.paypal-payment-method',
        '.paystack-payment-method',
        '.oxxo-payment-method',
        '.mercado-payment-method',
        '.suitpay-payment-method',
        '.noxpay-payment-method',
        '.credit-payment-method'
    ],

    /**
     * Initiates the payment data payload
     */
    initiatePaymentData: function (type, amount, post, recipient, firstName, lastName, billingAddress, country, city, state, postcode, availableCredit, streamId, messageId, coupon, couponDiscount, couponDiscountType) {
        checkout.paymentData = {
            type: type,
            amount: amount,
            post: post,
            recipient: recipient,
            firstName: firstName,
            lastName: lastName,
            billingAddress: billingAddress,
            country: country,
            city: city,
            state: state,
            postcode: postcode,
            availableCredit: availableCredit,
            stream: streamId,
            messageId: messageId,
            coupon: coupon,  // Cupom
            couponDiscount: couponDiscount,       // Valor do desconto
            couponDiscountType: couponDiscountType // Tipo do desconto
        };
        console.log("Dados do pagamento:", couponDiscount);
        console.log("Dados do pagamento:", couponDiscountType);
    },
    

    /**
     * Updates the payment form
     */
    updatePaymentForm: function () {
        $('#payment-type').val(checkout.paymentData.type);
        $('#post').val(checkout.paymentData.post);
        $('#recipient').val(checkout.paymentData.recipient);
        $('#provider').val(checkout.paymentData.provider);
        $('#paymentFirstName').val(checkout.paymentData.firstName);
        $('#paymentLastName').val(checkout.paymentData.lastName);
        $('#paymentBillingAddress').val(checkout.paymentData.billingAddress);
        $('#paymentCountry').val(checkout.paymentData.country);
        $('#paymentState').val(checkout.paymentData.state);
        $('#paymentPostcode').val(checkout.paymentData.postcode);
        $('#paymentCity').val(checkout.paymentData.city);
        $('#payment-deposit-amount').val(checkout.paymentData.totalAmount);
        $('#paymentTaxes').val(JSON.stringify(checkout.paymentData.taxes));
        $('#stream').val(checkout.paymentData.stream);
        $('#userMessage').val(checkout.paymentData.messageId);
        $('#coupon').val(checkout.paymentData.coupon);
        $('#coupon_Discount').val(checkout.paymentData.couponDiscount);
        $('#coupon_Discount_Type').val(checkout.paymentData.couponDiscountType);
        const phoneRaw = $('.uifield-phone').val() || '';
        $('#payment-user-phone').val(phoneRaw.replace(/\D/g, ''));
      
        console.log('[updatePaymentForm] phone sent:', $('#payment-user-phone').val());
    },

    stripe: null,

    /**
     * Instantiates the payment session
     */
    initPayment: function () {

        console.log('Test:',checkout.paymentData.couponDiscount)
        console.log('Test:',checkout.paymentData.couponDiscountType)
        console.log('Test:',checkout.paymentData.coupon)
        if (!checkout.checkoutAmountValidation()) {
            return false;
        }
    
        let processor = checkout.getSelectedPaymentMethod();
        if (!processor) {
            $('.payment-error').removeClass('d-none');
        }
    
        if (processor) {
            $('.paymentProcessorError').hide();
            $('.error-message').hide();
    
            // Aplica o desconto no amount caso o método de pagamento não seja "stripe"
            if (processor !== 'stripe') {
                let discount = checkout.paymentData.couponDiscount;
                let discountType = checkout.paymentData.couponDiscountType;
                
                if (discount && discountType) {
                    let originalAmount = parseFloat(checkout.paymentData.amount);
                    if (discountType === 'fixed') {
                        checkout.paymentData.amount = originalAmount - discount;
                    } else if (discountType === 'percent') {
                        checkout.paymentData.amount = originalAmount - (originalAmount * discount / 100);
                    }
                    // Garante que o valor não fique negativo e arredonda para duas casas decimais
                    checkout.paymentData.amount = Math.max(0, parseFloat(checkout.paymentData.amount.toFixed(2)));
                    console.log("Amount com desconto:", checkout.paymentData.amount);
                }
            }
    
            if (checkout.allowedPaymentProcessors.includes(processor)) {
                checkout.updatePaymentForm();
                if (!checkout.activePaymentButton || !checkout.activePaymentButton.length) {
                    checkout.activePaymentButton = $('.checkout-continue-btn');
                }
                checkout.toggleProcessingState(true);
                checkout.validateAllFields(() => {
                    $('.payment-button').trigger('click');
                });
            }
        }
    },

    /**
     * Runs backend validation check for billing data
     * @param callback
     */
    validateAllFields: function(callback){
        checkout.clearFormErrors();
        $.ajax({
            type: 'POST',
            data: $('#pp-buyItem').serialize(),
            url: app.baseUrl + '/payment/initiate/validate',
            success: function () {
                callback();
            },
            error: function (result) {
                checkout.toggleProcessingState(false);
                if(result.status === 500){
                    launchToast('danger',trans('Error'),result.responseJSON.message);
                }
                $.each(result.responseJSON.errors,function (field,error) {
                    let fieldElement = $('.uifield-'+field);
                    fieldElement.addClass('is-invalid');
                    fieldElement.parent().append(
                        `
                            <span class="invalid-feedback" role="alert">
                                <strong>${error}</strong>
                            </span>
                        `
                    );
                });
            }
        });
    },


    /**
     * Clears up dialog (all) form errors
     */
    clearFormErrors: function () {
        // Clearing up prev form errors
        $('.invalid-feedback').remove();
        $('input').removeClass('is-invalid');
    },

    /**
     * Returns currently selected payment method
     */
    getSelectedPaymentMethod: function () {
        const paypalProvider = $('.paypal-payment-provider').hasClass('selected');
        const stripeProvider = $('.stripe-payment-provider').hasClass('selected');
        const creditProvider = $('.credit-payment-provider').hasClass('selected');
        const coinbaseProvider = $('.coinbase-payment-provider').hasClass('selected');
        const nowPaymentsProvider = $('.nowpayments-payment-provider').hasClass('selected');
        const ccbillProvider = $('.ccbill-payment-provider').hasClass('selected');
        const paystackProvider = $('.paystack-payment-provider').hasClass('selected');
        const oxxoProvider = $('.oxxo-payment-provider').hasClass('selected');
        const mercadoProvider = $('.mercado-payment-provider').hasClass('selected');
        const suitpayProvider = $('.suitpay-payment-provider').hasClass('selected');
        const noxpayProvider = $('.noxpay-payment-provider').hasClass('selected');
        let val = null;
        if (paypalProvider) {
            val = 'paypal';
        } else if (stripeProvider) {
            val = 'stripe';
        } else if (creditProvider) {
            val = 'credit';
        } else if(coinbaseProvider){
            val = 'coinbase';
        } else if(nowPaymentsProvider){
            val = 'nowpayments';
        } else if(ccbillProvider){
            val = 'ccbill';
        } else if(paystackProvider){
            val = 'paystack';
        } else if(oxxoProvider){
            val = 'oxxo';
        } else if(mercadoProvider){
            val = 'mercado';
        } else if(suitpayProvider){
            val = 'suitpay';
        } else if(noxpayProvider){
            val = 'noxpay';
        }
        if (val) {
            checkout.paymentData.provider = val;
            return val;
        }
        return false;
    },
 
    /**
     * Validates the amount field
     * @returns {boolean}
     */
    checkoutAmountValidation: function () {
        const checkoutAmount = $('#checkout-amount').val();
        // Apply a tips min-max validation | Rest don't need any constrains
        if(checkout.paymentData.type == 'tip'){
            if ((checkoutAmount.length > 0 && checkoutAmount >= app.tipMinAmount && checkoutAmount <= app.tipMaxAmount)) {
                $('#checkout-amount').removeClass('is-invalid');
                $('#paypal-deposit-amount').val(checkoutAmount);
                if (checkout.paymentData.availableCredit < checkoutAmount) {
                    $(".credit-payment-provider").css("pointer-events", "none");
                }
                return true;
            } else {
                $('#checkout-amount').addClass('is-invalid');
                return false;
            }
        }
        return true;
    },

    /**
     * Validates FN field
     */
    validateFirstNameField: function () {
        let firstNameField = $('input[name="firstName"]');
        checkout.paymentData.firstName = firstNameField.val();
    },


 
    
    /**
     * Validates the phone field
     */
    validatePhoneField: function () {
        let phoneField = $('input[name="phone"]');
        let rawPhoneVal = phoneField.val();
        let phoneVal = rawPhoneVal.replace(/\D/g, ''); // remove tudo que não for número
    
        // Verifica se está vazio ou com quantidade inválida de dígitos
        if (rawPhoneVal.trim() === '' || phoneVal.length !== 11) {
            phoneField.addClass('is-invalid');
            $('#phone-error').text('Por favor, insira um número de celular válido com DDD.');
            checkout.paymentData.phone = '';
        } else {
            phoneField.removeClass('is-invalid');
            $('#phone-error').text('');
            checkout.paymentData.phone = rawPhoneVal; // mantém o valor formatado
        }
    },
    
    /**
     * Validates LN field
     */
    validateLastNameField: function () {
        let lastNameField = $('input[name="lastName"]');
        checkout.paymentData.lastName = lastNameField.val();
    },

    /**
     * Validates Adress field
     */
    validateBillingAddressField: function () {
        let billingAddressField = $('textarea[name="billingAddress"]');
        checkout.paymentData.billingAddress = billingAddressField.val();
    },

    /**
     * Validates city field
     */
    validateCityField: function () {
        let cityField = $('input[name="billingCity"]');
        checkout.paymentData.city = cityField.val();
    },

    /**
     * Validates state field
     */
    validateStateField: function () {
        let stateField = $('input[name="billingState"]');
        checkout.paymentData.state = stateField.val();
    },

    /**
     * Validates the ZIP code
     */
    validatePostcodeField: function () {
        let postcodeField = $('input[name="billingPostcode"]');
        checkout.paymentData.postcode = postcodeField.val();
    },

    /**
     * Validates the country field
     */
    validateCountryField: function () {
        let countryField = $('.country-select');
        let countryValidation = $('.country-select').find(':selected').val().length;
        let selectedCountry = $('.country-select').find(':selected');
        if (countryValidation) {
            countryField.removeClass('is-invalid');
            checkout.paymentData.country = selectedCountry.text();
        }
        else{
            checkout.paymentData.country = '';
        }
    },

    /**
     * Prefills user billing data, if available
     */
    prefillBillingDetails: function () {
        $('input[name="firstName"]').val(checkout.paymentData.firstName);
        $('input[name="lastName"]').val(checkout.paymentData.lastName);
        $('input[name="billingCity"]').val(checkout.paymentData.city);
        $('input[name="billingState"]').val(checkout.paymentData.state);
        $('input[name="billingPostcode"]').val(checkout.paymentData.postcode);
        $('textarea[name="billingAddress"]').val(checkout.paymentData.billingAddress);
        $('input[name="phone"]').val(checkout.paymentData.phone);
    },

    /**
     * Updates user details
     * @param userAvatar
     * @param username
     * @param name
     */
    updateUserDetails: function (userAvatar, username, name) {
        $('.payment-body .user-avatar').attr('src', userAvatar);
        $('.payment-body .name').text(name);
        $('.payment-body .username').text('@' + username);
    },

    /**
     * Fetches list of countries, in order to calculcate taxes
     */
    fillCountrySelectOptions: function () {
        $.ajax({
            type: 'GET',
            url: app.baseUrl + '/countries',
            success: function (result) {
                if (result !== null && typeof result.countries !== 'undefined' && result.countries.length > 0) {
                    $('.country-select').find('option').remove().end().append('<option value="">'+trans("Select a country")+'</option>');
                    $.each(result.countries, function (i, item) {
                        let selected = checkout.paymentData.country !== null && checkout.paymentData.country === item.name;
                        $('.country-select').append($('<option>', {
                            value: item.id,
                            text: item.name,
                            selected: selected
                        }).data({taxes: item.taxes}));
                        if (selected) {
                            checkout.updatePaymentSummaryData();
                        }
                    });
                }
            }
        });
    },

    /**
     * Updates payment summary data, taxes included
     */
    updatePaymentSummaryData: function () {
        let subtotalAmount = typeof checkout.paymentData.amount !== 'undefined' ? parseFloat(checkout.paymentData.amount) : 0.00;
        let countryInclusiveTaxesPercentage = 0.00;
        let countryExclusiveTaxesPercentage = 0.00;
        let taxesAmount = 0.00;
        let totalAmount = subtotalAmount;
        let inclusiveTaxesAmount = 0.00;
        let exclusiveTaxesAmount = 0.00;
        checkout.paymentData.totalAmount = subtotalAmount;
        let taxes = [];

        let couponDiscount = 0;
        if (checkout.paymentData.coupon && checkout.paymentData.coupon.discount_percent) {
            couponDiscount = parseFloat(checkout.paymentData.coupon.discount_percent);
            if (couponDiscount > 0) {
                const discountAmount = subtotalAmount * (couponDiscount / 100);
                subtotalAmount -= discountAmount;
                
                $('.taxes-details').prepend(
                    '<div class="row ml-2">' +
                    '<span class="col-sm left">' + checkout.paymentData.coupon.coupon_code + ' (' + couponDiscount + '% off)</span>' +
                    '<span class="col-sm right text-right text-success">' +
                    '<b>- ' + getWebsiteFormattedAmount(discountAmount.toFixed(2)) + '</b>' +
                    '</span>' +
                    '</div>'
                );
            }
        }

        // calculate taxes by country
        $('.taxes-details').html("");
        let selectedCountry = $('.country-select').find(':selected');
        if (selectedCountry !== null && selectedCountry.val() > 0) {
            let countryTaxes = selectedCountry.data('taxes');
            if (countryTaxes !== null) {
                if (countryTaxes.length > 0) {
                    for (let i = 0; i < countryTaxes.length; i++) {
                        let countryTaxPercentage = countryTaxes[i].percentage;
                        if (countryTaxPercentage !== null && countryTaxPercentage > 0) {
                            let countryTaxAmount = 0.00;
                            if (countryTaxes[i].type === 'exclusive') {
                                countryExclusiveTaxesPercentage += parseFloat(countryTaxPercentage);
                                taxes.push({
                                    countryTaxName: countryTaxes[i].name,
                                    type: 'exclusive',
                                    countryTaxPercentage: parseFloat(countryTaxPercentage),
                                    hidden: countryTaxes[i].hidden
                                });
                            } else {
                                countryInclusiveTaxesPercentage += parseFloat(countryTaxPercentage);
                                taxes.push({
                                    countryTaxAmount: countryTaxAmount,
                                    countryTaxName: countryTaxes[i].name,
                                    type: 'inclusive',
                                    countryTaxPercentage: parseFloat(countryTaxPercentage),
                                    hidden: countryTaxes[i].hidden
                                });
                            }
                        }
                    }
                }
            }
        }

        let formattedTaxes = {data: [], taxesTotalAmount: 0.00, subtotal: subtotalAmount.toFixed(2)};

        if (subtotalAmount > 0) {
            for (let j = 0; j < taxes.length; j++) {
                let taxAmount = 0.00;
                let inclusiveTaxesAmount = subtotalAmount - subtotalAmount / (1 + countryInclusiveTaxesPercentage.toFixed(2) / 100);
                if (taxes[j].type === 'inclusive') {
                    let countryTaxAmount = subtotalAmount - subtotalAmount / (1 + taxes[j].countryTaxPercentage.toFixed(2) / 100);
                    let remainingFees = inclusiveTaxesAmount - countryTaxAmount;
                    let amountWithoutRemainingFees = subtotalAmount - remainingFees;
                    taxAmount = amountWithoutRemainingFees - amountWithoutRemainingFees / (1 + taxes[j].countryTaxPercentage.toFixed(2) / 100);

                    try {
                        taxAmount = taxAmount.toString().match(/^-?\d+(?:\.\d{0,2})?/)[0];
                    } catch (e) {
                        taxAmount = taxAmount.toFixed(2);
                    }
                }

                if (taxes[j].type === 'exclusive') {
                    let amountWithInclusiveFeesDeducted = subtotalAmount - inclusiveTaxesAmount;
                    taxAmount = (taxes[j].countryTaxPercentage.toFixed(2) / 100) * amountWithInclusiveFeesDeducted;
                    taxAmount = taxAmount.toFixed(2);
                }

                formattedTaxes.data.push({
                    taxName: taxes[j].countryTaxName,
                    taxAmount: taxAmount,
                    taxPercentage: taxes[j].countryTaxPercentage,
                    taxType: taxes[j].type
                });

                if(!taxes[j].hidden) {
                    let taxType = taxes[j].type === 'inclusive' ? ' incl.' : '';
                    let item = "<div class=\"row ml-2\">\n" +
                        "<span class=\"col-sm left\">" + taxes[j].countryTaxName + " (" + taxes[j].countryTaxPercentage + "%" + taxType + ")</span>\n" +
                        "<span class=\"country-tax col-sm right text-right\">\n" +
                        "    <b>" + getWebsiteFormattedAmount(taxAmount) + "</b>\n" +
                        "</span>\n" +
                        "</div>";
                    $('.taxes-details').append(item);
                }
            }

            let subtotal = subtotalAmount;
            if (countryInclusiveTaxesPercentage > 0) {
                inclusiveTaxesAmount = subtotalAmount - subtotalAmount / (1 + countryInclusiveTaxesPercentage.toFixed(2) / 100);
                if (inclusiveTaxesAmount > 0) {
                    subtotal = subtotalAmount - inclusiveTaxesAmount;
                }
            }

            if (countryExclusiveTaxesPercentage > 0) {
                exclusiveTaxesAmount = (countryExclusiveTaxesPercentage.toFixed(2) / 100) * subtotal;
                totalAmount = totalAmount + exclusiveTaxesAmount;
            }

            if (formattedTaxes.data && formattedTaxes.data.length > 0) {
                for (let i = 0; i < formattedTaxes.data.length; i++) {
                    if (formattedTaxes.data[i]['taxAmount'] !== undefined) {
                        taxesAmount = taxesAmount + parseFloat(formattedTaxes.data[i]['taxAmount']);
                    }
                }
            }
            formattedTaxes.taxesTotalAmount = taxesAmount.toFixed(2);
            checkout.paymentData.totalAmount = totalAmount.toFixed(2);
        }

        checkout.paymentData.taxes = formattedTaxes;

        $('.available-credit').html('(' + getWebsiteFormattedAmount(checkout.paymentData.availableCredit) + ')');
        if (checkout.paymentData.availableCredit < totalAmount) {
            $(".credit-payment-provider").css("pointer-events", "none");
        }

        $('.subtotal-amount b').html(getWebsiteFormattedAmount(subtotalAmount.toFixed(2)));
        $('.total-amount b').html(getWebsiteFormattedAmount(totalAmount.toFixed(2)));
    },

    toggleCryptoPaymentProviders: function(toggle){
        let coinbasePaymentMethod = $('.coinbase-payment-method');
        let nowPaymentsPaymentMethod = $('.nowpayments-payment-method');
        if(toggle){
            if(coinbasePaymentMethod.hasClass('d-none')){
                coinbasePaymentMethod.removeClass('d-none');
            }
            if(nowPaymentsPaymentMethod.hasClass('d-none')){
                nowPaymentsPaymentMethod.removeClass('d-none');
            }
        } else {
            if(!coinbasePaymentMethod.hasClass('d-none')){
                coinbasePaymentMethod.addClass('d-none');
            }
            if(!nowPaymentsPaymentMethod.hasClass('d-none')){
                nowPaymentsPaymentMethod.addClass('d-none');
            }
        }

    },

    toggleProcessingState: function(isLoading){
        let $button = checkout.activePaymentButton && checkout.activePaymentButton.length ? checkout.activePaymentButton : $('.checkout-continue-btn');
        if (!$button || !$button.length) {
            return;
        }

        if (isLoading) {
            $button.addClass('is-processing').prop('disabled', true);
        } else {
            $button.removeClass('is-processing');
            const $phone = $('#paymentPhone');
            if ($phone.length) {
                const digits = $phone.val().replace(/\D/g, '');
                $button.prop('disabled', digits.length !== 11);
            } else {
                $button.prop('disabled', false);
            }
        }

        const $spinner = $button.find('.spinner-border');
        if ($spinner.length) {
            $spinner.toggleClass('d-none', !isLoading);
        }

        if (!isLoading) {
            checkout.activePaymentButton = null;
        }
    },

    syncNoxpayButton: function(){
        const $button = $('.checkout-noxpay-btn');
        if (!$button.length) {
            return;
        }

        const $method = $('.noxpay-payment-method');
        const shouldShow = $method.length && !$method.hasClass('d-none');
        $button.toggleClass('d-none', !shouldShow);
    },

    togglePaymentProvider: function(toggle, paymentMethodClass){
        let paymentMethod = $(paymentMethodClass);
        if(toggle){
            if(paymentMethod.hasClass('d-none')){
                paymentMethod.removeClass('d-none');
            }
        } else {
            if(!paymentMethod.hasClass('d-none')){
                paymentMethod.addClass('d-none');
            }
        }

        if (paymentMethodClass === '.noxpay-payment-method') {
            checkout.syncNoxpayButton();
        }

    },

    togglePaymentProviders: function(toggle, paymentMethodClasses){
        paymentMethodClasses.forEach(function(paymentMethodClass){
            checkout.togglePaymentProvider(toggle, paymentMethodClass);
        });

    },
};

// create the copy to clipboard function
function copyToClipboard(text) {
    // create a textarea
    var copyElement = document.createElement("textarea");

    // set the text of the textarea to the text we want to copy
    copyElement.textContent = text;

    // append the textarea to the body
    document.body.appendChild(copyElement);

    // select the textarea
    copyElement.select();

    // copy the text
    navigator.clipboard.writeText(copyElement.value);

    // remove the textarea
    document.body.removeChild(copyElement);
}

 function copySuitpayPaymentCode(paymentCode) {
   // copy paymentCode to clipboard
   copyToClipboard(paymentCode);

   alert('Payment code copied to clipboard!')
 }

 function copyNoxpayPaymentCode(paymentCode) {
   copyToClipboard(paymentCode);

   alert('Payment code copied to clipboard!')
 }
