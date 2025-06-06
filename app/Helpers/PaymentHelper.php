<?php
/**
 * Created by PhpStorm.
 * User: Lab #2
 * Date: 6/6/2021
 * Time: 4:10 PM.
 */

namespace App\Helpers;

use App\User;
use DateTime;
use DateTimeZone;
use App\Model\Tax;
use App\Model\Post;
use App\Model\UserPixel;
use MercadoPago\SDK;
use PayPal\Api\Item;
use PayPal\Api\Plan;
use App\Model\Stream;
use PayPal\Api\Patch;
use PayPal\Api\Payer;
use Ramsey\Uuid\Uuid;
use Yabacon\Paystack;
use App\Model\Country;
use GuzzleHttp\Client;
use PayPal\Api\Amount;
use PayPal\Api\Payment;
use PayPal\Api\Currency;
use PayPal\Api\ItemList;
use Stripe\StripeClient;
use App\Services\SuitPay;
use PayPal\Api\Agreement;
use App\Model\Transaction;
use App\Model\UserMessage;
use App\Model\Subscription;
use App\Model\WalletRetainedBalance;
use MercadoPago\Preference;
use PayPal\Api\ChargeModel;
use PayPal\Rest\ApiContext;
use Illuminate\Http\Request;
use PayPal\Api\PatchRequest;
use PayPal\Api\RedirectUrls;
use PayPal\Common\PayPalModel;
use PayPal\Api\PaymentExecution;
use PayPal\Api\PaymentDefinition;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use PayPal\Api\MerchantPreferences;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use PayPal\Auth\OAuthTokenCredential;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use PayPal\Api\AgreementStateDescriptor;
use App\Providers\InvoiceServiceProvider;
use App\Providers\PaymentsServiceProvider;
use App\Providers\SettingsServiceProvider;
use Yabacon\Paystack\Exception\ApiException;
use App\Providers\NotificationServiceProvider;
use App\Providers\GenericHelperServiceProvider;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Api\Transaction as PaypalTransaction;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Model\Coupon;


class PaymentHelper
{
    /**
     * Holds up the credentials for paypal API.
     *
     * @var
     */
    private $paypalApiContext;

    private $experienceId;

    public function initiatePaypalContext()
    {
        if (!$this->paypalApiContext instanceof ApiContext) {
            // PP API Context
            $this->paypalApiContext = new ApiContext(new OAuthTokenCredential(config('paypal.client_id'), config('paypal.secret')));
            $this->paypalApiContext->setConfig(config('paypal.settings'));

            // PP Payment Experience
            $this->experienceId = $this->generateWebProfile();
        }
    }

    public function getPaypalApiContext()
    {
        return $this->paypalApiContext;
    }

    public function generatePaypalSubscriptionByTransaction(Transaction $transaction)
    {
        try {
            $now = new \DateTime();
            $now->setTimezone(new \DateTimeZone('UTC'));
            //initiate the recurring payment, send back the link for the user to approve it.
            if ($transaction['payment_provider'] === Transaction::PAYPAL_PROVIDER) {
                $plan = $this->createPayPalSubscriptionPlan($transaction);
                $agreement = $this->createPayPalSubscriptionAgreement($transaction, $this->getActiveAgreementPlan($plan->getId()));

                $existingSubscription = $this->getSubscriptionBySenderAndReceiverAndProvider(
                    $transaction['sender_user_id'],
                    $transaction['recipient_user_id'],
                    Transaction::PAYPAL_PROVIDER
                );
                if ($existingSubscription != null) {
                    $subscription = $existingSubscription;
                    $subscription['paypal_agreement_id'] = $agreement->getId();
                    $subscription['paypal_plan_id'] = $plan->getId();
                } else {
                    $subscription = $this->createSubscriptionFromTransaction($transaction, $plan);
                    $subscription['paypal_agreement_id'] = $agreement->getId();
                }
                $subscription->save();
                $transaction['paypal_transaction_token'] = $this->getPayPalTransactionTokenFromApprovalLink($agreement);
                $transaction['subscription_id'] = $subscription['id'];

                return $agreement->getApprovalLink();
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    private function createPayPalSubscriptionPlan(Transaction $transaction)
    {
        $plan = new Plan();
        $plan->setName($this->getPaymentDescriptionByTransaction($transaction))
            ->setDescription($this->getPaymentDescriptionByTransaction($transaction))
            ->setState('ACTIVE')
            ->setType('INFINITE');

        $paymentDefinition = $this->createPayPalSubscriptionPaymentDefinition($transaction);
        $merchantPreferences = $this->createPayPalSubscriptionMerchantPreferences($transaction);
        $plan->setMerchantPreferences($merchantPreferences);
        $plan->setPaymentDefinitions([$paymentDefinition]);

        try {
            $plan = $plan->create($this->paypalApiContext);
        } catch (\Exception $exception) {
            return $this->redirectByTransaction($transaction, __("Could not create subscription plan: ") . $exception->getMessage());
        }

        return $plan;
    }

    private function createPayPalSubscriptionPaymentDefinition(Transaction $transaction)
    {
        $paymentDefinitionName = $this->getPaymentDescriptionByTransaction($transaction);

        $paymentDefinition = new PaymentDefinition();
        $paymentDefinition->setName($paymentDefinitionName)
            ->setType('REGULAR')
            ->setFrequency('Month')
            ->setFrequencyInterval(strval(PaymentsServiceProvider::getSubscriptionMonthlyIntervalByTransactionType($transaction->type)))
            ->setCycles(0)
            ->setAmount(new Currency(['value' => $transaction['amount'], 'currency' => $transaction['currency']]));
        $chargeModel = new ChargeModel();
        $chargeModel->setType('SHIPPING')
            ->setAmount(new Currency(['value' => 0, 'currency' => $transaction['currency']]));

        $paymentDefinition->setChargeModels([$chargeModel]);

        return $paymentDefinition;
    }

    private function createPayPalSubscriptionMerchantPreferences(Transaction $transaction)
    {
        $merchantPreferences = new MerchantPreferences();
        $merchantPreferences->setReturnUrl(route('payment.executePaypalPayment'))
            ->setCancelUrl(route('payment.executePaypalPayment'))
            ->setAutoBillAmount('yes')
            ->setInitialFailAmountAction('CONTINUE')
            ->setMaxFailAttempts('0')
            ->setSetupFee(new Currency(['value' => $transaction['amount'], 'currency' => $transaction['currency']]));

        return $merchantPreferences;
    }

    public function createPayPalSubscriptionAgreement(Transaction $transaction, Plan $plan)
    {
        try {
            $agreementDate = new DateTime('+'.PaymentsServiceProvider::getSubscriptionMonthlyIntervalByTransactionType($transaction->type).' month', new \DateTimeZone('UTC'));
            $agreement = new Agreement();

            $agreement->setName($this->getPaymentDescriptionByTransaction($transaction))
                ->setDescription($this->getPaymentDescriptionByTransaction($transaction))
                ->setStartDate($agreementDate->format('Y-m-d\TH:i:s\Z'));
            $payer = new Payer();
            $payer->setPaymentMethod('paypal');
            $agreement->setPayer($payer);
            $agreement->setPlan($plan);

            $agreement = $agreement->create($this->paypalApiContext);
        } catch (\Exception $ex) {
            if ($ex instanceof PayPalConnectionException) {
                return $this->redirectByTransaction($transaction, __("Could not verify PayPal agreement:") . $ex->getData());
            }

            return $this->redirectByTransaction($transaction, __("Could not verify PayPal agreement:") . $ex->getMessage());
        }

        return $agreement;
    }

    public function getPayPalTransactionTokenFromApprovalLink(Agreement $agreement)
    {
        $token = explode('token=', $agreement->getApprovalLink());
        if (array_key_exists(1, $token)) {
            return $token[1];
        } else {
            throw new BadRequestHttpException(__('Failed to fetch PayPal transaction token'));
        }
    }

    private function getActiveAgreementPlan($planId)
    {
        $plan = new Plan();
        $plan->setId($planId);
        $patch = new Patch();
        $value = new PayPalModel('{
	       "state":"ACTIVE"
	     }');
        $patch->setOp('replace')
            ->setPath('/')
            ->setValue($value);
        $patchRequest = new PatchRequest();
        $patchRequest->addPatch($patch);

        try {
            $plan->update($patchRequest, $this->paypalApiContext);
        } catch (\Exception $ex) {
            throw new BadRequestHttpException(__("Could not update PayPal plan: ") . $ex->getMessage());
        }

        return $plan;
    }

    private function createSubscriptionFromTransaction(Transaction $transaction, Plan $plan = null)
    {
        $subscription = new Subscription();

        if ($transaction['recipient_user_id'] != null && $transaction['sender_user_id'] != null) {
            $subscription['recipient_user_id'] = $transaction['recipient_user_id'];
            $subscription['sender_user_id'] = $transaction['sender_user_id'];
            $subscription['provider'] = $transaction['payment_provider'];
            $subscription['type'] = $transaction['type'];
            if ($plan != null) {
                $subscription['paypal_plan_id'] = $plan->getId();
            }
            $subscription['status'] = Transaction::PENDING_STATUS;
        }

        return $subscription;
    }

    public function verifyPayPalAgreement($agreementId, $transaction = null, $paypalPaymentId = null)
    {
        try {
            $this->initiatePaypalContext();
            $agreement = Agreement::get($agreementId, $this->paypalApiContext);
            $nowUtc = new DateTime('now', new DateTimeZone('UTC'));
            $now = new DateTime();

            $agreementLastPaymentDate = new DateTime($agreement->getAgreementDetails()->getLastPaymentDate());
            $agreementNextPaymentDate = new DateTime($agreement->getAgreementDetails()->getNextBillingDate());
            $subscription = Subscription::query()->where(['paypal_agreement_id' => $agreementId])->first();
            if ($nowUtc > $agreementLastPaymentDate
                && $nowUtc < $agreementNextPaymentDate
                && strtolower($agreement->getState()) === 'active'
                && $subscription != null
                && $subscription->expires_at < $now) {
                // if it's already active it means we only need to renew this subscription
                if ($subscription->status == Subscription::ACTIVE_STATUS
                    || $subscription->status == Subscription::SUSPENDED_STATUS
                    || $subscription->status == Subscription::EXPIRED_STATUS) {
                    $this->createSubscriptionRenewalTransaction($subscription, $paymentSucceeded = true, $paypalPaymentId);

                // else this webhook comes for first payment of this subscription
                } else {
                    // find last initiated transaction by subscription and update it's status
                    $existingTransaction = Transaction::query()->where([
                        'subscription_id' => $subscription->id,
                        'provider' => Transaction::PAYPAL_PROVIDER,
                        'status' => Transaction::INITIATED_STATUS,
                    ])->latest();

                    if ($existingTransaction instanceof Transaction) {
                        $existingTransaction->status = Transaction::APPROVED_STATUS;

                        $existingTransaction->save();

                        NotificationServiceProvider::createNewSubscriptionNotification($subscription);
                    }
                }

                $agreementNextPaymentDate->setTimezone($now->getTimezone());
                $subscriptionBody = [
                    'status' => Subscription::ACTIVE_STATUS,
                    'amount' => $agreement->getPlan()->getPaymentDefinitions()[0]->getAmount()->getValue(),
                    'expires_at' => $agreementNextPaymentDate,
                ];

                Subscription::query()->where('id', $subscription->id)->update($subscriptionBody);

                if ($transaction != null) {
                    $transaction->status = Transaction::APPROVED_STATUS;
                }

                return $agreement;
            }
        } catch (\Exception $exception) {
            if ($exception instanceof PayPalConnectionException) {
                return $this->redirectByTransaction($transaction, __("Could not verify PayPal agreement:") . $exception->getData());
            }

            return $this->redirectByTransaction($transaction, __("Could not verify PayPal agreement:") . $exception->getMessage());
        }
    }

    public function initiateOneTimePaypalTransaction(Transaction $transaction)
    {
        // Item info
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $item_1 = new Item();

        $item_1->setName($this->getPaymentDescriptionByTransaction($transaction))// item name
        ->setCurrency(config('app.site.currency_code'))
            ->setQuantity(1)
            ->setPrice($transaction['amount']); // unit price

        // Add item to list
        $item_list = new ItemList();
        $item_list->setItems([$item_1]);

        $amount = new Amount();
        $amount->setCurrency(config('app.site.currency_code'))
            ->setTotal($transaction['amount']);

        $paypalTransaction = new PaypalTransaction();
        $paypalTransaction->setAmount($amount)
            ->setItemList($item_list)
            ->setDescription($this->getPaymentDescriptionByTransaction($transaction));

        // Cancel URLs
        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl(route('payment.executePaypalPayment'))
            ->setCancelUrl(route('payment.executePaypalPayment'));

        // Generating new Payment
        $payment = new Payment();
        $payment->setIntent('Sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirect_urls)
            ->setTransactions([$paypalTransaction])
            ->setExperienceProfileId($this->experienceId);

        $payment->create($this->paypalApiContext);
        $transaction['paypal_transaction_token'] = $payment->getToken();
        $transaction['paypal_transaction_id'] = $payment->getId();

        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }

        return $redirect_url;
    }

    /**
     * Generate a paypal web experience profile.
     *
     * @return string
     */
    private function generateWebProfile()
    {
        $flowConfig = new \PayPal\Api\FlowConfig();
        $flowConfig->setLandingPageType('Billing');
        $flowConfig->setUserAction('commit');
        $flowConfig->setReturnUriHttpMethod('GET');

        $presentation = new \PayPal\Api\Presentation();
        $presentation->setBrandName(getSetting('site.name'))
            ->setLocaleCode('US')
            ->setReturnUrlLabel(__('Return'))
            ->setNoteToSellerLabel(__('Thanks!'));

        $inputFields = new \PayPal\Api\InputFields();
        $inputFields->setAllowNote(true)
            ->setNoShipping(1)
            ->setAddressOverride(0);

        $webProfile = new \PayPal\Api\WebProfile();
        $webProfile->setName(getSetting('site.name').uniqid())
            ->setFlowConfig($flowConfig)
            ->setPresentation($presentation)
            ->setInputFields($inputFields)
            ->setTemporary(true);

        try {
            // Use this call to create a profile.
            $createProfileResponse = $webProfile->create($this->paypalApiContext);

            return $createProfileResponse->id;
        } catch (\Exception $ex) {
            Log::error('Payments webprofile failure: '.$ex->getMessage());
        }
    }

    public function executePaypalAgreementPayment($transaction)
    {
        $subscription = Subscription::query()->where('id', $transaction->subscription_id)->first();
        if ($subscription != null) {
            if ($subscription->paypal_agreement_id != null) {
                $agreement = $this->verifyPayPalAgreement($subscription->paypal_agreement_id, $transaction);
            } else {
                try {
                    $this->initiatePaypalContext();
                    $agreement = new Agreement();

                    $agreement->execute($transaction->paypal_transaction_token, $this->paypalApiContext);

                    $now = new DateTime();
                    $nowUtc = new DateTime('now', new DateTimeZone('UTC'));
                    $nextBillingDateUtc = new DateTime($agreement->getAgreementDetails()->getNextBillingDate());
                    $nextBillingDate = new DateTime($agreement->getAgreementDetails()->getNextBillingDate(), $now->getTimezone());

                    if ($agreement->getAgreementDetails()->getNextBillingDate() !== null) {
                        $subscription->expires_at = $nextBillingDate;
                    }

                    $subscription->paypal_agreement_id = $agreement->getId();

                    if ($nowUtc < $nextBillingDateUtc) {
                        $subscription->status = Subscription::ACTIVE_STATUS;
                        $subscription->amount = $agreement->getPlan()->getPaymentDefinitions()[0]->getAmount()->getValue();
                        $transaction->status = Transaction::APPROVED_STATUS;
                    } else {
                        $subscription->status = Subscription::EXPIRED_STATUS;
                    }
                } catch (\Exception $ex) {
                    if ($ex instanceof PayPalConnectionException) {
                        return $this->redirectByTransaction($transaction, "Could not verify PayPal agreement: {$ex->getData()}\"");
                    }

                    return $this->redirectByTransaction($transaction, "Could not verify PayPal agreement: {$ex->getMessage()}\"");
                }

                $subscription->paypal_agreement_id = $agreement->getId();

                $subscription->save();

                if ($subscription != null && $subscription->status === Subscription::ACTIVE_STATUS) {
                    NotificationServiceProvider::createNewSubscriptionNotification($subscription);
                }
            }

            if ($agreement instanceof Agreement) {
                if ($agreement->getPayer() != null && $agreement->getPayer()->getPayerInfo() != null) {
                    $transaction['paypal_payer_id'] = $agreement->getPayer()->getPayerInfo()->getPayerId();
                }
            }

            if ($transaction->status == Transaction::APPROVED_STATUS) {
                // credit receiver for transaction
                $this->creditReceiverForTransaction($transaction);
            }
        } else {
            return $this->redirectByTransaction($transaction, __("Couldn't find a subscription for this payment"));
        }
    }

    public function executeOneTimePaypalPayment(Request $request, $transaction, $paymentId)
    {
        //Executing the payment
        try {
            // Building up the API Context
            $this->initiatePaypalContext();
            $payment = Payment::get($paymentId, $this->paypalApiContext);
            $execution = new PaymentExecution();
            $execution->setPayerId($request->get('PayerID'));

            $result = $payment->execute($execution, $this->paypalApiContext);

            if ($result->getState() == 'approved') {
                $saleStatus = Transaction::APPROVED_STATUS;
            } elseif ($result->getState() == 'failed') {
                $saleStatus = Transaction::CANCELED_STATUS;
            } else {
                $saleStatus = Transaction::PENDING_STATUS;
            }

            $transaction->status = $saleStatus;
            $transaction->paypal_transaction_id = $result->id;
            $transaction->paypal_payer_id = $request->get('PayerID');

            if ($transaction->status == Transaction::APPROVED_STATUS) {
                // credit receiver for transaction
                $this->creditReceiverForTransaction($transaction);
            }
        } catch (\Exception $ex) {
            Log::error('Failed executing one time paypal payment: '.$ex->getMessage());
        }
    }

    public function send_meta_event_pixel($event, $transaction)
    {

        $user_recipient = User::query()->where('id', $transaction->recipient_user_id)->first();
        $pixels = UserPixel::where("user_id", $transaction->recipient_user_id)->get()->all();
        $pixel_obj = array();
        foreach($pixels as $pixel){
            $pixel_obj[$pixel->type."-head"] = $pixel->head;
            $pixel_obj[$pixel->type."-body"] = $pixel->body;
        }

        if(!isset($pixel_obj['meta-body']) || !isset($pixel_obj['meta-meta'])){
            return 1;
        }

        $user_sender = User::query()->where('id', $transaction->sender_user_id)->first();
        $facebook_access_token = $pixel_obj['meta-body'];
        $facebook_pixel_id = $pixel_obj['meta-head'];
        $amount = $transaction->amount;
        if ( !empty($facebook_pixel_id) && !empty($facebook_access_token)) {
            $url = 'https://graph.facebook.com/v18.0/' . $facebook_pixel_id . '/events?access_token=' . $facebook_access_token;
            $fn =  hash('sha256', $user_sender->name);
            $ph = hash('sha256', $user_sender->email);
    
            switch ($event) {
                case 'Purchase':
                    $data = [
                        [
                            'event_name'    => $event,
                            'event_time'    => time(),
                            'action_source' => 'website',
                            'user_data'     => [
                                'em'          => [$ph],
                                'fn'          => [$fn],
                            ],
                            'custom_data'   => ['currency' => 'BRL', 'value' => (float) $amount]
                        ]
                    ];
                    break;
                case 'InitiateCheckout':
                    $data = [
                        [
                            'event_name'    => $event,
                            'event_time'    => time(),
                            'action_source' => 'website',
                            'user_data'     => [
                                'em'          => [$ph],
                                'fn'          => [$fn],
                            ],
                            'custom_data'   => ['currency' => 'BRL', 'value' => (float) $amount]
                        ]
                    ];
                    break;
                case 'CompleteRegistration':
                    $data = [
                        [
                            'event_name'    => $event,
                            'event_time'    => time(),
                            'action_source' => 'website',
                            'user_data'     => [
                                'em'          => [$ph],
                                'fn'          => [$fn],
                            ]
                        ]
                    ];
                    break;
                default:
                    $data = [];
                    break;
            }
    
            $options = [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['data' => $data]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ];
            $curl = curl_init();
            curl_setopt_array($curl, $options);
            $response = curl_exec($curl);
            curl_close($curl);
        }
    }

    public function send_google_event_pixel($event, $transaction)
    {

        $user_recipient = User::query()->where('id', $transaction->recipient_user_id)->first();
        $pixels = UserPixel::where("user_id", $transaction->recipient_user_id)->get()->all();
        $pixel_obj = array();
        foreach($pixels as $pixel){
            $pixel_obj[$pixel->type."-head"] = $pixel->head;
            $pixel_obj[$pixel->type."-body"] = $pixel->body;
        }

        if(!isset($pixel_obj['google-body']) || !isset($pixel_obj['google-head'])){
            return 1;
        }

        $google_access_token = $pixel_obj['google-body'];
        $google_pixel_id = $pixel_obj['google-head'];
        $user_sender = User::query()->where('id', $transaction->sender_user_id)->first();
        $amount = $transaction->amount;
        if ( !empty($google_pixel_id) && !empty($google_access_token)) {
            $url = 'https://www.google-analytics.com/mp/collect?measurement_id=' . $facebook_pixel_id . '&api_secret=' . $google_access_token;
            $fn =  hash('sha256', $user_sender->name);
            $ph = hash('sha256', $user_sender->email);
    
            switch ($event) {
                case 'Purchase':
                    $data = [
                        [
                            'user_id'    => $transaction->recipient_user_id,
                            'client_id'    => $transaction->sender_user_id,
                            'user_data'     => [
                                'sha256_email_address'          => [$ph],
                                'fn'          => [$fn],
                            ],
                            'events' => [[
                                name => 'purchase'
                            ]]
                        ]
                    ];
                    break;
                case 'InitiateCheckout':
                    $data = [
                        [
                            'user_id'    => $transaction->recipient_user_id,
                            'client_id'    => $transaction->sender_user_id,
                            'user_data'     => [
                                'InitiateCheckout'          => [$ph],
                                'fn'          => [$fn],
                            ],
                            'events' => [[
                                name => 'purchase'
                            ]]
                        ]
                    ];
                    break;
                case 'CompleteRegistration':
                    $data = [
                        [
                            'user_id'    => $transaction->recipient_user_id,
                            'client_id'    => $transaction->sender_user_id,
                            'user_data'     => [
                                'InitiateCheckout'          => [$ph],
                                'fn'          => [$fn],
                            ],
                            'events' => [[
                                name => 'CompleteRegistration'
                            ]]
                        ]
                    ];
                    break;
                default:
                    $data = [];
                    break;
            }
    
            $options = [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['data' => $data]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ];
            $curl = curl_init();
            curl_setopt_array($curl, $options);
            $response = curl_exec($curl);
            curl_close($curl);
        }
    }

    public function creditReceiverForTransaction($transaction)
    {
        if ($transaction->type != null && $transaction->status == Transaction::APPROVED_STATUS) {
            $user = User::query()->where('id', $transaction->recipient_user_id)->first();
            if ($user != null) {
                $userWallet = $user->wallet;
    
                $amountWithTaxesDeducted = $transaction->amount;
    
                // Aplicar taxas se o tipo da transação não for 'deposit'
                if ($transaction->type != 'deposit') {
                    // Calculando as taxas
                    // Exemplo de como adicionar logs para depuração
                    $taxes = PaymentsServiceProvider::calculateTaxesForTransaction($transaction);
                    Log::info('Taxas calculadas:', $taxes); // Isso vai registrar os valores das taxas no log

                    if (isset($taxes['inclusiveTaxesAmount'])) {
                        Log::info('Taxa inclusiva antes de deduzir: ' . $taxes['inclusiveTaxesAmount']);
                        $amountWithTaxesDeducted -= $taxes['inclusiveTaxesAmount'];
                        Log::info('Montante após deduzir taxa inclusiva: ' . $amountWithTaxesDeducted);
                    }

                    if (isset($taxes['exclusiveTaxesAmount'])) {
                        Log::info('Taxa exclusiva antes de deduzir: ' . $taxes['exclusiveTaxesAmount']);
                        $amountWithTaxesDeducted -= $taxes['exclusiveTaxesAmount'];
                        Log::info('Montante após deduzir taxa exclusiva: ' . $amountWithTaxesDeducted);
                    }
                }
    
                // Verifica se o payment_provider é 'stripe' para reter saldo
                if ($transaction->payment_provider == 'stripe' && $transaction->type != 'deposit') {
                    WalletRetainedBalance::create([
                        'retained_balance' => $amountWithTaxesDeducted,
                        'wallet_id' => $userWallet->id,
                    ]);
                } else {
                    // Atualiza o saldo para outros provedores
                    $userWallet->update(['total' => $userWallet->total + $amountWithTaxesDeducted]);
                }
    
                // Armazena dados de pagamento suitpay na sessão
                if ($transaction->payment_provider == 'suitpay') {
                    Session::put('transaction_pixel', [
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                        'transaction_id' => $transaction->id,
                    ]);
                }
            }
        }
    }
    
    
    

    public function updateTransactionByStripeSessionId($sessionId)
    {
        $transaction = Transaction::query()->where(['stripe_session_id' => $sessionId])->first();
        if ($transaction != null) {
            try {
                $stripeClient = new StripeClient(getSetting('payments.stripe_secret_key'));
                $stripeSession = $stripeClient->checkout->sessions->retrieve($sessionId);
                if ($stripeSession != null) {
                    if (isset($stripeSession->payment_status)) {
                        $transaction->stripe_transaction_id = $stripeSession->payment_intent;
                        if ($stripeSession->payment_status == 'paid') {
                            if ($transaction->status != Transaction::APPROVED_STATUS) {
                                $transaction->status = Transaction::APPROVED_STATUS;
                                $subscription = Subscription::query()->where('id', $transaction->subscription_id)->first();
                                if ($subscription != null && $this->isSubscriptionPayment($transaction->type)) {
                                    if ($stripeSession->subscription != null) {
                                        $subscription->stripe_subscription_id = $stripeSession->subscription;
                                        $stripeSubscription = $stripeClient->subscriptions->retrieve($stripeSession->subscription);
                                        if($stripeSubscription != null){
                                            $latestInvoiceForSubscription = $stripeClient->invoices->retrieve($stripeSubscription->latest_invoice);
                                            if($latestInvoiceForSubscription != null){
                                                $transaction->stripe_transaction_id = $latestInvoiceForSubscription->payment_intent;
                                            }
                                        }
                                    }

                                    $expiresDate = new \DateTime('+'.PaymentsServiceProvider::getSubscriptionMonthlyIntervalByTransactionType($transaction->type).' month', new \DateTimeZone('UTC'));
                                    if ($subscription->status != Subscription::ACTIVE_STATUS) {
                                        $subscription->status = Subscription::ACTIVE_STATUS;
                                        $subscription->expires_at = $expiresDate;

                                        NotificationServiceProvider::createNewSubscriptionNotification($subscription);
                                    } else {
                                        $subscription->expires_at = $expiresDate;
                                    }

                                    $subscription->update();

                                    $this->creditReceiverForTransaction($transaction);
                                } else {
                                    $this->creditReceiverForTransaction($transaction);
                                }
                            }
                        } else {
                            $transaction->status = Transaction::CANCELED_STATUS;

                            $subscription = Subscription::query()->where('id', $transaction->subscription_id)->first();

                            if ($subscription != null && $subscription->status == Subscription::ACTIVE_STATUS && $subscription->expires_at <= new \DateTime()) {
                                $subscription->status = Subscription::CANCELED_STATUS;

                                $subscription->update();
                            }
                        }
                    }

                    $transaction->update();
                }
            } catch (\Exception $exception) {
                Log::error($exception->getMessage());
            }
        }

        return $transaction;
    }

    public function generateStripeSubscriptionByTransaction($transaction)
    {
        $existingSubscription = $this->getSubscriptionBySenderAndReceiverAndProvider(
            $transaction['sender_user_id'],
            $transaction['recipient_user_id'],
            Transaction::STRIPE_PROVIDER);

        if ($existingSubscription != null) {
            $subscription = $existingSubscription;
        } else {
            $subscription = $this->createSubscriptionFromTransaction($transaction);
            $subscription['amount'] = $transaction['amount'];

            $subscription->save();
        }
        $transaction['subscription_id'] = $subscription['id'];

        return $subscription;
    }

    public function createSubscriptionRenewalTransaction($subscription, $paymentSucceeded, $paymentId = null)
    {
        $transaction = new Transaction();
        $transaction['sender_user_id'] = $subscription->sender_user_id;
        $transaction['recipient_user_id'] = $subscription->recipient_user_id;
        $transaction['type'] = Transaction::SUBSCRIPTION_RENEWAL;
        $transaction['status'] = $paymentSucceeded ? Transaction::APPROVED_STATUS : Transaction::DECLINED_STATUS;
        $transaction['amount'] = $subscription->amount;
        $transaction['currency'] = config('app.site.currency_code');
        $transaction['payment_provider'] = $subscription->provider;
        $transaction['subscription_id'] = $subscription->id;

        // find latest transaction for subscription to get taxes
        $lastTransactionForSubscription = Transaction::query()
            ->where('subscription_id', $subscription->id)
            ->orderBy('created_at', 'DESC')
            ->first();

        if ($lastTransactionForSubscription != null) {
            $transaction['taxes'] = $lastTransactionForSubscription->taxes;
        }

        if ($paymentId != null) {
            if ($transaction['payment_provider'] === Transaction::PAYPAL_PROVIDER) {
                $transaction['paypal_transaction_id'] = $paymentId;
            } elseif ($transaction['payment_provider'] === Transaction::STRIPE_PROVIDER) {
                $transaction['stripe_transaction_id'] = $paymentId;
            } elseif ($transaction['payment_provider'] === Transaction::CCBILL_PROVIDER) {
                $transaction['ccbill_subscription_id'] = $paymentId;
            }
        }

        $transaction->save();

        $this->creditReceiverForTransaction($transaction);

        if ($transaction['status'] === Transaction::APPROVED_STATUS && $transaction['type'] === Transaction::CREDIT_PROVIDER) {
            $this->deductMoneyFromUserWalletForCreditTransaction($transaction, $subscription->subscriber->wallet);
        }

        try {
            $invoice = InvoiceServiceProvider::createInvoiceByTransaction($transaction);
            if ($invoice != null) {
                $transaction->invoice_id = $invoice->id;
                $transaction->save();
            }
        } catch (\Exception $exception) {
            Log::error("Failed generating invoice for transaction: ".$transaction->id." error: ".$exception->getMessage());
        }

        return $transaction;
    }

    public function cancelPaypalAgreement($agreementId)
    {
        $this->initiatePaypalContext();
        $agreement = Agreement::get($agreementId, $this->getPaypalApiContext());
        if ($agreement != null) {
            $agreementStateDescriptor = new AgreementStateDescriptor();
            $agreementStateDescriptor->setNote(__('Cancel by the client.'));

            $agreement->cancel($agreementStateDescriptor, $this->getPaypalApiContext());
        }
    }

    public function cancelStripeSubscription($stripeSubscriptionId)
    {
        $stripe = new \Stripe\StripeClient(getSetting('payments.stripe_secret_key'));

        $stripe->subscriptions->cancel($stripeSubscriptionId);
    }

    public function deductMoneyFromUserForRefundedTransaction($transaction)
    {
        if ($transaction->type != null && $transaction->status == Transaction::REFUNDED_STATUS) {
            switch ($transaction->type) {
                case Transaction::DEPOSIT_TYPE:
                case Transaction::TIP_TYPE:
                case Transaction::CHAT_TIP_TYPE:
                case Transaction::ONE_MONTH_SUBSCRIPTION:
                case Transaction::THREE_MONTHS_SUBSCRIPTION:
                case Transaction::SIX_MONTHS_SUBSCRIPTION:
                case Transaction::YEARLY_SUBSCRIPTION:
                    $user = User::query()->where('id', $transaction->recipient_user_id)->first();
                    if ($user != null) {
                        $user->wallet->update(['total' => $user->wallet->total - floatval($transaction->amount)]);
                    }
                    break;
            }
        }
    }

    public function getLoggedUserAvailableAmount()
    {
        $amount = 0.00;
        if (Auth::user() != null && Auth::user()->wallet != null) {
            $amount = Auth::user()->wallet->total;
        }

        return $amount;
    }

    public function generateOneTimeCreditTransaction($transaction)
    {
        $userAvailableAmount = $this->getLoggedUserAvailableAmount();
        if ($transaction['amount'] <= $userAvailableAmount) {
            $transaction['status'] = Transaction::APPROVED_STATUS;
        }
    }

    public function deductMoneyFromUserWalletForCreditTransaction($transaction, $userWallet)
    {
        if ($userWallet != null) {
            $userWallet->update([
                'total' => $userWallet->total - floatval($transaction['amount']),
            ]);
        }
    }

    private function getSubscriptionBySenderAndReceiverAndProvider($senderId, $receiverId, $provider)
    {
        $queryCriteria = [
            'recipient_user_id' => $receiverId,
            'sender_user_id' => $senderId,
            'provider' => $provider,
        ];

        return Subscription::query()->where($queryCriteria)->first();
    }

    public function generateCreditSubscriptionByTransaction($transaction)
    {
        $existingSubscription = $this->getSubscriptionBySenderAndReceiverAndProvider(
            $transaction['sender_user_id'],
            $transaction['recipient_user_id'],
            Transaction::CREDIT_PROVIDER
        );

        if ($existingSubscription != null) {
            $subscription = $existingSubscription;
        } else {
            $subscription = $this->createSubscriptionFromTransaction($transaction);
        }
        $subscription['amount'] = $transaction['amount'];
        $subscription['expires_at'] = new \DateTime('+' . PaymentsServiceProvider::getSubscriptionMonthlyIntervalByTransactionType($transaction->type) . ' months', new \DateTimeZone('UTC'));
        $subscription['status'] = Subscription::ACTIVE_STATUS;
        $transaction['status'] = Transaction::APPROVED_STATUS;

        $subscription->save();

        // only send the notification for new subs
        if($existingSubscription === null){
            NotificationServiceProvider::createNewSubscriptionNotification($subscription);
        }
        $transaction['subscription_id'] = $subscription['id'];

        return $subscription;
    }

    public function createNewTipNotificationForCreditTransaction($transaction)
    {
        if ($transaction != null
            && $transaction->payment_provider === Transaction::CREDIT_PROVIDER
            && $transaction->status === Transaction::APPROVED_STATUS
            && ($transaction->type === Transaction::TIP_TYPE || $transaction->type === Transaction::CHAT_TIP_TYPE)) {
            NotificationServiceProvider::createNewTipNotification($transaction);
        }
    }

    public function generateStripeSessionByTransaction(Transaction $transaction)
    {
        $redirectLink = null;
        $transactionType = $transaction->type;
        if ($transactionType == null || empty($transactionType)) {
            return null;
        }

        try {
            \Stripe\Stripe::setApiKey(getSetting('payments.stripe_secret_key'));
            if ($this->isSubscriptionPayment($transactionType)) {
                // generate stripe product
                $product = \Stripe\Product::create([
                    'name' => $this->getPaymentDescriptionByTransaction($transaction),
                ]);

                // generate stripe price
                $price = \Stripe\Price::create([
                    'product' => $product->id,
                    'unit_amount' => $transaction->amount * 100,
                    'currency' => config('app.site.currency_code'),
                    'recurring' => [
                        'interval' => 'month',
                        'interval_count' => PaymentsServiceProvider::getSubscriptionMonthlyIntervalByTransactionType($transactionType),
                    ],
                ]);

                $stripeLineItems = [
                    'price' => $price->id,
                    'quantity' => 1,
                ];
            } else {
                $stripeLineItems = [
                    'price_data' => [
                        # To accept `oxxo`, all line items must have currency: mxn
                        'currency' => config('app.site.currency_code'),
                        'product_data' => [
                            'name' => $this->getPaymentDescriptionByTransaction($transaction),
                            'description' => $this->getPaymentDescriptionByTransaction($transaction),
                        ],
                        'unit_amount' => $transaction->amount * 100,
                    ],
                    'quantity' => 1,
                ];
            }

            $data = [
                'payment_method_types' => ['card'],
                'line_items' => [$stripeLineItems],
                'locale' => 'auto',
                'customer_email' => Auth::user()->email,
                'metadata' => [
                    'transactionType' => $transaction->type,
                    'user_id' => Auth::user()->id,
                ],
                'mode' => $this->isSubscriptionPayment($transaction->type) ? 'subscription' : 'payment',
                'success_url' => route('payment.checkStripePaymentStatus').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.checkStripePaymentStatus').'?session_id={CHECKOUT_SESSION_ID}',
            ];
            
            // Se existir um cupom na transação, adiciona o desconto
            if (!empty($transaction->coupon)) {
                $data['discounts'] = [
                    [
                        'coupon' => $transaction->coupon
                    ]
                ];
            }
            if($transaction->payment_provider === Transaction::OXXO_PROVIDER) {
                $data['payment_method_types'] = ['oxxo'];
            }
            $session = \Stripe\Checkout\Session::create($data);

            $transaction['stripe_session_id'] = $session->id;
            $redirectLink = $session->url;
        } catch (\Exception $e) {
            Log::error('Failed generating stripe session for transaction: '.$transaction->id.' error: '.$e->getMessage());
        }

        return $redirectLink;
    }

    /**
     * Verify if payment is made for a subscription
     *
     * @param $transactionType
     * @return bool
     */
    public function isSubscriptionPayment($transactionType)
    {
        return $transactionType != null
            && ($transactionType === Transaction::SIX_MONTHS_SUBSCRIPTION
                || $transactionType === Transaction::THREE_MONTHS_SUBSCRIPTION
                || $transactionType === Transaction::ONE_MONTH_SUBSCRIPTION
                || $transactionType === Transaction::YEARLY_SUBSCRIPTION);
    }

    /**
     * Get payment description by transaction type
     *
     * @param $transaction
     * @return string
     */
    public function getPaymentDescriptionByTransaction($transaction)
    {
        $description = 'Default payment description';
        if ($transaction != null) {
            $recipientUsername = null;
            if ($transaction->recipient_user_id != null) {
                $recipientUser = User::query()->where(['id' => $transaction->recipient_user_id])->first();
                if ($recipientUser != null) {
                    $recipientUsername = $recipientUser->name;
                }
            }

            if ($this->isSubscriptionPayment($transaction->type)) {
                if ($recipientUsername == null) {
                    $recipientUsername = 'creator';
                }

                $description = $recipientUsername.' for '.SettingsServiceProvider::getWebsiteFormattedAmount($transaction->amount);
            } else {
                if ($transaction->type === Transaction::DEPOSIT_TYPE) {
                    $description = SettingsServiceProvider::getWebsiteFormattedAmount($transaction->amount).' '. __('wallet popup');
                } elseif ($transaction->type === Transaction::TIP_TYPE || $transaction->type === Transaction::CHAT_TIP_TYPE) {
                    $tipPaymentDescription = SettingsServiceProvider::getWebsiteFormattedAmount($transaction->amount).' tip';
                    if ($transaction->recipient_user_id != null) {
                        $recipientUser = User::query()->where(['id' => $transaction->recipient_user_id])->first();
                        if ($recipientUser != null) {
                            $tipPaymentDescription = $tipPaymentDescription.' for '.$recipientUser->name;
                        }
                    }

                    $description = $tipPaymentDescription;
                } elseif ($transaction->type === Transaction::POST_UNLOCK) {
                    $description = __('Unlock post for').' '.SettingsServiceProvider::getWebsiteFormattedAmount($transaction->amount);
                } elseif ($transaction->type === Transaction::STREAM_ACCESS) {
                    $description = __('Join streaming for').' '.SettingsServiceProvider::getWebsiteFormattedAmount($transaction->amount);
                } elseif ($transaction->type === Transaction::MESSAGE_UNLOCK) {
                    $description = __('Unlock message for').' '.SettingsServiceProvider::getWebsiteFormattedAmount($transaction->amount);
                }
            }
        }

        return $description;
    }

    /**
     * Redirect user to proper page after payment process
     *
     * @param $transaction
     * @param null $message
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectByTransaction($transaction, $message = null)
    {
        // Not sure why translation locale is not being applied here, re-appliying it
        App::setLocale(GenericHelperServiceProvider::getPreferredLanguage());

        $errorMessage = __('Payment failed.');
        if ($message != null) {
            $errorMessage = $message;
        }
        if ($transaction != null) {
            // handles approved status
            $recipient = User::query()->where(['id' => $transaction->recipient_user_id])->first();
            if ($transaction->status === Transaction::APPROVED_STATUS) {
                $successMessage = __('Payment succeeded');
                if ($this->isSubscriptionPayment($transaction->type)) {
                    $successMessage = __('You can now access this user profile.');
                } elseif ($transaction->type === Transaction::DEPOSIT_TYPE) {
                    $key = SettingsServiceProvider::leftAlignedCurrencyPosition()
                        ? 'You have been credited :currencySymbol:amount Happy spending!'
                        : 'You have been credited :amount:currencySymbol Happy spending!';
                    $successMessage = __($key, ['amount' => $transaction->amount, 'currencySymbol' => SettingsServiceProvider::getWebsiteCurrencySymbol()]);
                } elseif ($transaction->type === Transaction::TIP_TYPE || $transaction->type === Transaction::CHAT_TIP_TYPE) {
                    $key = SettingsServiceProvider::leftAlignedCurrencyPosition()
                        ? 'You successfully sent a tip of :currencySymbol:amount.'
                        : 'You successfully sent a tip of :amount:currencySymbol.';
                    $successMessage = __($key, ['amount' => $transaction->amount, 'currencySymbol' => SettingsServiceProvider::getWebsiteCurrencySymbol()]);
                } elseif ($transaction->type === Transaction::POST_UNLOCK) {
                    $successMessage = __('You successfully unlocked this post.');
                } elseif ($transaction->type === Transaction::STREAM_ACCESS) {
                    $successMessage = __('You successfully paid for this streaming.');
                } elseif ($transaction->type === Transaction::MESSAGE_UNLOCK) {
                    $successMessage = __('You successfully unlocked this message.');
                }

                return $this->handleRedirectByTransaction($transaction, $recipient, $successMessage, $success = true);
                // handles any other status
            } 

            // check if transaction is initiated and payment provider is suitpay
            if ($transaction->status === Transaction::INITIATED_STATUS && $transaction->payment_provider === Transaction::SUITPAY_PROVIDER ) {
                $successMessage = __('Your payment have been successfully initiated but needs to await for approval. Please scan the QR code below to complete the payment.');

                // check if suitpay payment code is set and transaction type is deposit
                if ($transaction->suitpay_payment_code != null 
                        && $transaction->type === Transaction::DEPOSIT_TYPE 
                        || $transaction->type === Transaction::TIP_TYPE 
                        || $transaction->type === Transaction::CHAT_TIP_TYPE
                        || $transaction->type === Transaction::POST_UNLOCK
                        || $transaction->type === Transaction::STREAM_ACCESS
                        || $transaction->type === Transaction::MESSAGE_UNLOCK
                        || $transaction->type === Transaction::ONE_MONTH_SUBSCRIPTION 
                        || $transaction->type === Transaction::THREE_MONTHS_SUBSCRIPTION
                        || $transaction->type === Transaction::SIX_MONTHS_SUBSCRIPTION
                        || $transaction->type === Transaction::YEARLY_SUBSCRIPTION
                    ) {

                    // set suitpay payment data to session
                    $this->setSuitpayPaymentDataToSession($transaction);

                    return $this->handleRedirectByTransaction($transaction, $recipient, $successMessage, $success = true);
                }

                return $this->handleRedirectByTransaction($transaction, $recipient, $errorMessage, $success = false);
            
            }
            
            return $this->handleRedirectByTransaction($transaction, $recipient, $errorMessage, $success = false);
            
        } else {
            return Redirect::route('feed')
                ->with('error', $errorMessage);
        }
    }

    /**
     * Handles redirect by transaction type
     *
     * @param $transaction
     * @param $recipient
     * @param $message
     * @param bool $success
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleRedirectByTransaction($transaction, $recipient, $message, $success = false)
    {
        $labelType = $success ? 'success' : 'error';
        if ($this->isSubscriptionPayment($transaction->type)) {
            if($transaction->payment_provider === Transaction::CCBILL_PROVIDER && $transaction->status === Transaction::INITIATED_STATUS) {
                $labelType = 'warning';
                $message = __('Your payment have been successfully initiated but needs to await for approval');
            }

            if($transaction->stream_id){
                return Redirect::route('public.stream.get', ['streamID' => $transaction->stream_id, 'slug' => $transaction->stream->slug])
                    ->with($labelType, $message);
            }
			return Redirect::route('profile', ['username' => $recipient->username])
                ->with($labelType, $message);
        } elseif ($transaction->type === Transaction::DEPOSIT_TYPE) {
            if(in_array($transaction->payment_provider, Transaction::PENDING_PAYMENT_PROCESSORS)){
                if($transaction->status === Transaction::INITIATED_STATUS || $transaction->status === Transaction::PENDING_STATUS){
                    $labelType = 'warning';
                    $message = __('Your payment have been successfully initiated but needs to await for approval');
                } else if($transaction->status === Transaction::CANCELED_STATUS){
                    $message = __('Payment canceled');
                }
            } else if($transaction->payment_provider === Transaction::MANUAL_PROVIDER) {
                $labelType = 'warning';
                $message = __('Your payment have been successfully initiated but needs to await for processing');
            }

            return Redirect::route('my.settings', ['type' => 'wallet'])
                ->with($labelType, $message);
        } elseif ($transaction->type === Transaction::TIP_TYPE  || $transaction->type === Transaction::CHAT_TIP_TYPE) {
            if(in_array($transaction->payment_provider, Transaction::PENDING_PAYMENT_PROCESSORS)){
                if($transaction->status === Transaction::INITIATED_STATUS){
                    $labelType = 'warning';
                    $message = __('Your payment have been successfully initiated but needs to await for approval');
                } else if($transaction->status === Transaction::CANCELED_STATUS){
                    $message = __('Payment canceled');
                }
            }

            if ($transaction->post_id != null) {
                return Redirect::route('posts.get', ['post_id' => $transaction->post_id, 'username' => $recipient->username])
                    ->with($labelType, $message);
            }
            if($transaction->stream_id){
                return Redirect::route('public.stream.get', ['streamID' => $transaction->stream_id, 'slug' => $transaction->stream->slug])
                    ->with($labelType, $message);
            }
            if($transaction->type === Transaction::CHAT_TIP_TYPE) {
                return Redirect::route('my.messenger.get', ['tip'=>1])->with($labelType, $message);
            }
            return Redirect::route('profile', ['username' => $recipient->username])
                ->with($labelType, $message);
        } elseif ($transaction->type === Transaction::POST_UNLOCK) {
            if(in_array($transaction->payment_provider, Transaction::PENDING_PAYMENT_PROCESSORS)) {
                if($transaction->status === Transaction::INITIATED_STATUS || $transaction->status === Transaction::PENDING_STATUS){
                    $labelType = 'warning';
                    $message = __('Your payment have been successfully initiated but needs to await for approval');
                } else if($transaction->status === Transaction::CANCELED_STATUS){
                    $message = __('Payment canceled');
                }
            }
            return Redirect::route('posts.get', ['post_id' => $transaction->post_id, 'username' => $recipient->username])
                ->with($labelType, $message);
        } elseif ($transaction->type === Transaction::STREAM_ACCESS) {
            if(in_array($transaction->payment_provider, Transaction::PENDING_PAYMENT_PROCESSORS)) {
                if($transaction->status === Transaction::INITIATED_STATUS || $transaction->status === Transaction::PENDING_STATUS){
                    $labelType = 'warning';
                    $message = __('Your payment have been successfully initiated but needs to await for approval');
                } else if($transaction->status === Transaction::CANCELED_STATUS){
                    $message = __('Payment canceled');
                }
            }
            return Redirect::route('public.stream.get', ['streamID' => $transaction->stream_id, 'slug' => $transaction->stream->slug])
                ->with($labelType, $message);
        } elseif ($transaction->type === Transaction::MESSAGE_UNLOCK) {
            if(in_array($transaction->payment_provider, Transaction::PENDING_PAYMENT_PROCESSORS)) {
                if($transaction->status === Transaction::INITIATED_STATUS || $transaction->status === Transaction::PENDING_STATUS){
                    $labelType = 'warning';
                    $message = __('Your payment have been successfully initiated but needs to await for approval');
                } else if($transaction->status === Transaction::CANCELED_STATUS){
                    $message = __('Payment canceled');
                }
            }
            return Redirect::route('my.messenger.get', ['messageUnlock' => 1, 'token' => $transaction->user_message_id])->with($labelType, $message);
        }
    }

    /**
     * Generate CoinBase transaction by an api call
     * @param $transaction
     * @return string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function generateCoinBaseTransaction($transaction)
    {
        $redirectUrl = null;
        $httpClient = new Client();
        self::generateCoinbaseTransactionToken($transaction);
        $coinBaseCheckoutRequest = $httpClient->request('POST', Transaction::COINBASE_API_BASE_PATH . '/charges', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-CC-Api-Key' => getSetting('payments.coinbase_api_key'),
                    'X-CC-Version' => '2018-03-22',
                ],
                'body' => json_encode(array_merge_recursive([
                    'name' => self::getPaymentDescriptionByTransaction($transaction),
                    'description' => self::getPaymentDescriptionByTransaction($transaction),
                    'local_price' => [
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                    ],
                    'pricing_type' => 'fixed_price',
                    'metadata' => [],
                    'redirect_url' => route('payment.checkCoinBasePaymentStatus').'?token='.$transaction->coinbase_transaction_token,
                    'cancel_url' => route('payment.checkCoinBasePaymentStatus').'?token='.$transaction->coinbase_transaction_token,
                ]))
            ]
        );

        $response = json_decode($coinBaseCheckoutRequest->getBody(), true);
        if (isset($response['data'])) {
            if (isset($response['data']['id'])) {
                $transaction->coinbase_charge_id = $response['data']['id'];
            }

            if (isset($response['data']['hosted_url'])) {
                $redirectUrl = $response['data']['hosted_url'];
            }
        }

        return $redirectUrl;
    }

    /**
     * Generate unique coinbase transaction token used later as identifier
     * @param $transaction
     * @throws \Exception
     */
    private function generateCoinbaseTransactionToken($transaction)
    {
        // generate unique token for transaction
        do {
            $id = Uuid::uuid4()->getHex();
        } while (Transaction::query()->where('coinbase_transaction_token', $id)->first() != null);
        $transaction->coinbase_transaction_token = $id;
    }

    /**
     * Update transaction by coinbase charge details
     * @param $transaction
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkAndUpdateCoinbaseTransaction($transaction)
    {
        if ($transaction != null && $transaction->status != Transaction::APPROVED_STATUS
            && $transaction->payment_provider === Transaction::COINBASE_PROVIDER && $transaction->coinbase_charge_id != null) {
            $coinbaseChargeStatus = self::getCoinbaseChargeStatus($transaction);
            if($coinbaseChargeStatus === 'CANCELED'){
                $transaction->status = Transaction::CANCELED_STATUS;
            } elseif ($coinbaseChargeStatus === 'COMPLETED') {
                $transaction->status = Transaction::APPROVED_STATUS;
                self::creditReceiverForTransaction($transaction);
            }
        }
    }

    /**
     * Get coinbase charge latest status
     * @param $transaction
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getCoinbaseChargeStatus($transaction)
    {
        $httpClient = new Client();
        $coinBaseCheckoutRequest = $httpClient->request('GET', Transaction::COINBASE_API_BASE_PATH . '/charges/' . $transaction->coinbase_charge_id, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-CC-Api-Key' => getSetting('payments.coinbase_api_key'),
                    'X-CC-Version' => '2018-03-22',
                ]
            ]
        );
        $coinbaseChargeLastStatus = 'NEW';
        $response = json_decode($coinBaseCheckoutRequest->getBody(), true);
        if (isset($response['data']) && isset($response['data']['timeline'])) {
            $coinbaseChargeLastStatus = $response['data']['timeline'][count($response['data']['timeline']) - 1]['status'];
        }

        return $coinbaseChargeLastStatus;
    }

    /**
     * Generate now payments transaction
     * @param $transaction
     * @return |null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function generateNowPaymentsTransaction($transaction)
    {
        $redirectUrl = null;
        $httpClient = new Client();
        $orderId = self::generateNowPaymentsOrderId($transaction);
        $coinBaseCheckoutRequest = $httpClient->request('POST', Transaction::NOWPAYMENTS_API_BASE_PATH . 'invoice', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => getSetting('payments.nowpayments_api_key'),
                ],
                'body' => json_encode(array_merge_recursive([
                    'price_amount' => $transaction->amount,
                    'price_currency' => $transaction->currency,
                    'ipn_callback_url' => route('nowPayments.payment.update'),
                    'order_description' => self::getPaymentDescriptionByTransaction($transaction),
                    'order_id' => $orderId,
                    'success_url' => route('payment.checkNowPaymentStatus').'?orderId='.$orderId,
                    'cancel_url' => route('payment.checkNowPaymentStatus').'?orderId='.$orderId,
                ]))
            ]
        );

        $response = json_decode($coinBaseCheckoutRequest->getBody(), true);
        if (isset($response['payment_id'])) {
            $transaction->nowpayments_payment_id = $response['payment_id'];
        }
        if(isset($response['order_id'])) {
            $transaction->nowpayments_order_id = $response['order_id'];
        }
        if(isset($response['invoice_url'])) {
            $redirectUrl = $response['invoice_url'];
        }

        return $redirectUrl;
    }

    /**
     * Fetch latest now payments transaction status
     * @param $transaction
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getNowPaymentsTransactionData($transaction)
    {
        $transactionData = [];
        $httpClient = new Client();
        $coinBaseCheckoutRequest = $httpClient->request('GET', Transaction::NOWPAYMENTS_API_BASE_PATH . 'payment/' . $transaction->nowpayments_payment_id, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => getSetting('payments.nowpayments_api_key'),
                ]
            ]
        );
        $response = json_decode($coinBaseCheckoutRequest->getBody(), true);
        if(isset($response['payment_status'])) {
            $transactionData['status'] = $response['payment_status'];
        }
        if(isset($response['payment_id'])) {
            $transactionData['id'] = $response['payment_id'];
        }

        return $transactionData;
    }

    /**
     * @param $transaction
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkAndUpdateNowPaymentsTransaction($transaction)
    {
        if ($transaction != null && $transaction->status === Transaction::INITIATED_STATUS
            && $transaction->payment_provider === Transaction::NOWPAYMENTS_PROVIDER) {
            $nowpaymentsPaymentData = self::getNowPaymentsTransactionData($transaction);
            $nowpaymentsPaymentStatus = isset($nowpaymentsPaymentData['status']) ? $nowpaymentsPaymentData['status'] : null;
            $nowpaymentsPaymentId = isset($nowpaymentsPaymentData['id']) ? $nowpaymentsPaymentData['id'] : null;
            if($nowpaymentsPaymentStatus === 'finished'){
                $transaction->status = Transaction::APPROVED_STATUS;
                self::creditReceiverForTransaction($transaction);
                NotificationServiceProvider::createTipNotificationByTransaction($transaction);
                NotificationServiceProvider::sendApprovedDepositTransactionEmailNotification($transaction);
                NotificationServiceProvider::createPPVNotificationByTransaction($transaction);
            } elseif (in_array($nowpaymentsPaymentStatus, ['failed', 'expired'])) {
                $transaction->status = Transaction::DECLINED_STATUS;
            } elseif ($nowpaymentsPaymentId && in_array($nowpaymentsPaymentStatus, ['waiting', 'confirming', 'sending'])) {
                $transaction->nowpayments_payment_id = $nowpaymentsPaymentId;
                $transaction->status = Transaction::PENDING_STATUS;
            } elseif ($nowpaymentsPaymentStatus === 'partially_paid') {
                $transaction->status = Transaction::PARTIALLY_PAID_STATUS;
                NotificationServiceProvider::sendNowPaymentsPartiallyPaidTransactionEmailNotification($transaction);
            }
        }
    }

    /**
     * @param $transaction
     * @return string
     * @throws \Exception
     */
    private function generateNowPaymentsOrderId($transaction)
    {
        // generate unique token for transaction
        do {
            $id = Uuid::uuid4()->getHex();
        } while (Transaction::query()->where('nowpayments_order_id', $id)->first() != null);
        $transaction->nowpayments_order_id = $id;

        return $id;
    }

    /**
     * Generates a unique identifier for ccbill transaction
     * @param $transaction
     * @return string
     * @throws \Exception
     */
    private function generateCCBillUniqueTransactionToken($transaction)
    {
        // generate unique token for transaction
        do {
            $id = Uuid::uuid4()->getHex();
        } while (Transaction::query()->where('ccbill_payment_token', $id)->first() != null);
        $transaction->ccbill_payment_token = $id;

        return $id;
    }

    /**
     * @param $transaction
     * @return int|null
     * @throws \Exception
     */
    public function generateCCBillOneTimePaymentTransaction($transaction){
        $redirectUrl = null;
        if(PaymentsServiceProvider::ccbillCredentialsProvided()) {
            // generate a unique token for transaction and prepare dynamic pricing for the flex form
            $this->generateCCBillUniqueTransactionToken($transaction);

            $redirectUrl = $this->generateCCBillRedirectUrlByTransaction($transaction);
        }

        return $redirectUrl;
    }

    /**
     * Generates redirect url for ccbill payment
     * @param $transaction
     * @return int|string
     */
    private function generateCCBillRedirectUrlByTransaction($transaction){
        $user = Auth::user();
        $country = Country::query()->where('name', $user->country)->first();
        $amount = $transaction->amount;
        $ccBillInitialPeriod=$this->getCCBillRecurringPeriodInDaysByTransaction($transaction);
        $ccBillNumRebills=99;
        $isSubscriptionPayment = $this->isSubscriptionPayment($transaction->type);
        $ccBillClientAcc = getSetting('payments.ccbill_account_number');
        $ccBillClientSubAccRecurring = getSetting('payments.ccbill_subaccount_number_recurring');
        $ccBillClientSubAccOneTime = getSetting('payments.ccbill_subaccount_number_one_time');
        $ccBillSalt = getSetting('payments.ccbill_salt_key');
        $ccBillFlexFormId = getSetting('payments.ccbill_flex_form_id');
        $ccBillCurrencyCode = $this->getCCBillCurrencyCodeByCurrency(SettingsServiceProvider::getAppCurrencyCode());
        $ccBillRecurringPeriod = $this->getCCBillRecurringPeriodInDaysByTransaction($transaction);
        $billingAddress = urlencode($user->billing_address);
        $billingFirstName = $user->first_name;
        $billingLastName = $user->last_name;
        $billingEmail = $user->email;
        $billingCity = $user->city;
        $billingState = $user->state;
        $billingPostcode = $user->postcode;
        $billingCountry = $country != null ? $country->country_code : $user->country;
        $ccBillFormDigest = $isSubscriptionPayment
            ? md5(number_format(floatval($amount), 2).$ccBillInitialPeriod.$amount.$ccBillRecurringPeriod.$ccBillNumRebills.$ccBillCurrencyCode.$ccBillSalt)
            : md5(number_format(floatval($amount), 2).$ccBillInitialPeriod.$ccBillCurrencyCode.$ccBillSalt);

        // common form metadata for both one time & recurring payments
        $redirectUrl = Transaction::CCBILL_FLEX_FORM_BASE_PATH.$ccBillFlexFormId.
            '?clientAccnum='.$ccBillClientAcc.'&initialPrice='.$amount.
            '&initialPeriod='.$ccBillInitialPeriod.'&currencyCode='.$ccBillCurrencyCode.'&formDigest='.$ccBillFormDigest.
            '&customer_fname='.$billingFirstName.'&customer_lname='.$billingLastName.'&address1='.$billingAddress.
            '&email='.$billingEmail.'&city='.$billingCity.'&state='.$billingState.'&zipcode='.$billingPostcode.
            '&country='.$billingCountry.'&token='.$transaction->ccbill_payment_token;

        // set client sub account for recurring payments & add extra params
        if($isSubscriptionPayment){
            $redirectUrl .= '&clientSubacc='.$ccBillClientSubAccRecurring.'&recurringPrice='.$amount.'&recurringPeriod='.$ccBillRecurringPeriod.'&numRebills='.$ccBillNumRebills;
        // set client sub account for one time payments & add extra params
        } else {
            $redirectUrl .= '&clientSubacc='.$ccBillClientSubAccOneTime;
        }

        return $redirectUrl;
    }

    /**
     * Get ccbill subscription recurring billing period in days
     * @param $transaction
     * @return float|int
     */
    public function getCCBillRecurringPeriodInDaysByTransaction($transaction) {
        return PaymentsServiceProvider::getSubscriptionMonthlyIntervalByTransactionType($transaction->type) * 30;
    }

    /**
     * @param $currency
     * @return mixed
     */
    public function getCCBillCurrencyCodeByCurrency($currency) {
        $availableCurrencies = [
            'EUR' => '978',
            'AUD' => '036',
            'CAD' => '124',
            'GBP' => '826',
            'JPY' => '392',
            'USD' => '840',
        ];

        return $availableCurrencies[$currency];
    }

    /**
     * @param $transaction
     * @return int|string|null
     * @throws \Exception
     */
    public function generateCCBillSubscriptionPayment($transaction){
        $redirectUrl = null;
        if(PaymentsServiceProvider::ccbillCredentialsProvided()) {
            // generate a unique token for transaction and prepare dynamic pricing for the flex form
            $this->generateCCBillUniqueTransactionToken($transaction);
            $this->generateCCBillSubscriptionByTransaction($transaction);
            $redirectUrl = $this->generateCCBillRedirectUrlByTransaction($transaction);
        }

        return $redirectUrl;
    }

    /**
     * @param $transaction
     * @return Subscription|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     * @throws \Exception
     */
    public function generateCCBillSubscriptionByTransaction($transaction)
    {
        $existingSubscription = $this->getSubscriptionBySenderAndReceiverAndProvider(
            $transaction['sender_user_id'],
            $transaction['recipient_user_id'],
            Transaction::CCBILL_PROVIDER
        );

        if ($existingSubscription != null) {
            $subscription = $existingSubscription;
        } else {
            $subscription = $this->createSubscriptionFromTransaction($transaction);
            $subscription['amount'] = $transaction['amount'];
            $subscription['ccbill_subscription_id'] = $transaction['ccbill_subscription_id'];

            $subscription->save();
        }
        $transaction['subscription_id'] = $subscription['id'];

        return $subscription;
    }

    /**
     * Makes the call to CCBill API to cancel a subscription
     * @param $stripeSubscriptionId
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancelCCBillSubscription($stripeSubscriptionId)
    {
        $client = new Client(['debug' => fopen('php://stderr', 'w')]);
        $cancellationData = [
            'clientAccnum' => getSetting('payments.ccbill_account_number'),
            'clientSubacc' => getSetting('payments.ccbill_subaccount_number_recurring'),
            'username' => getSetting('payments.ccbill_datalink_username'),
            'password' => getSetting('payments.ccbill_datalink_password'),
            'subscriptionId' => $stripeSubscriptionId,
            'action' => 'cancelSubscription',
        ];
        if(getSetting('payments.ccbill_skip_subaccount_from_cancellations')){
            unset($cancellationData['clientSubacc']);
        }
        $res = $client->request('GET', 'https://datalink.ccbill.com/utils/subscriptionManagement.cgi', [
            'query' => $cancellationData,
        ]);
        $response = $res->getBody()->getContents();
        if($response) {
            $responseAsArray = str_getcsv($response, "\n");
            if($responseAsArray && isset($responseAsArray[0]) && isset($responseAsArray[1])) {
                if($responseAsArray[0] === 'results' && $responseAsArray[1] === '1') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param $transaction
     * @return string
     * @throws \Exception
     */
    private function generatePaystackUniqueTransactionToken($transaction)
    {
        // generate unique token for transaction
        do {
            $id = Uuid::uuid4()->getHex();
        } while (Transaction::query()->where('paystack_payment_token', $id)->first() != null);
        $transaction->paystack_payment_token = $id;

        return $id;
    }

    /**
     * @param $transaction
     * @param $email
     * @return mixed
     * @throws \Exception
     */
    public function generatePaystackTransaction($transaction, $email) {
        $paystack = new Paystack(getSetting('payments.paystack_secret_key'));
        $reference = self::generatePaystackUniqueTransactionToken($transaction);
        $paystackTransaction = $paystack->transaction->initialize([
            'amount'=>$transaction->amount * 100,
            'email'=>$email,
            'reference'=>$reference
        ]);

        return $paystackTransaction->data->authorization_url;
    }

    /**
     * Calls PayStack API to verify payment status and updates transaction in our side accordingly
     * @param $reference
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function verifyPaystackTransaction($reference){
        $transaction = null;
        if($reference){
            $transaction = Transaction::query()->where('paystack_payment_token', $reference)->first();
            if($transaction && $transaction->status !== Transaction::APPROVED_STATUS) {
                $paystack = new Paystack(getSetting('payments.paystack_secret_key'));
                try
                {
                    $paystackTransaction = $paystack->transaction->verify([
                        'reference'=>$reference
                    ]);

                    if ('success' === $paystackTransaction->data->status) {
                        $transaction->status = Transaction::APPROVED_STATUS;
                        $transaction->save();

                        $this->creditReceiverForTransaction($transaction);
                        NotificationServiceProvider::createTipNotificationByTransaction($transaction);
                        NotificationServiceProvider::createPPVNotificationByTransaction($transaction);
                    }
                } catch(ApiException $e){
                    Log::error("Failed verifying paystack transaction: ".$e->getMessage());
                }
            }
        }

        return $transaction;
    }
 
    public function generatePixPaymentTransaction($transaction, $user) 
    {
        $reference = self::generateSuitPayUniqueTransactionToken($transaction);

        $data = [
            'requestNumber' => $reference,
            'dueDate' => now()->format('Y-m-d'),
            'amount' => $transaction->amount,
            'shippingAmount' => 0.0,
            'discountAmount' => 0.0,
            'usernameCheckout' => $user->username,
            'callbackUrl' => route('checkSuitpayPaymentStatus'),
            'client' => [
                'name' => $user->name,
                'email' => $user->email,
                'document' => '927.300.300-18',
                'phoneNumber' => $user->phone,
            ],
        ];

        $response = Http::withHeaders([
            'ci' => config('services.suitpay.client_id'),
            'cs' => config('services.suitpay.client_secret'),
        ])->post('https://ws.suitpay.app/api/v1/gateway/request-qrcode', $data);


        if ($response->successful()) {
            $data = $response->json();
            $transaction->suitpay_payment_transaction_id = $data['idTransaction'];
            $transaction->suitpay_payment_code = $data['paymentCode'];
            $transaction->save();
        }
    }


    

    public function validateTransaction($transaction, $recipientUser) {
        $valid = false;
        if($transaction) {
            $exclusiveTaxesAmount = 0;


            $taxes = PaymentsServiceProvider::calculateTaxesForTransaction($transaction);
            if(isset($taxes['exclusiveTaxesAmount'])) {
                $exclusiveTaxesAmount = $taxes['exclusiveTaxesAmount'];
            }
            $transactionAmountWithoutTaxes = (string)($transaction['amount'] - $exclusiveTaxesAmount);

            // Note*: Doing (string) comparison due to PHP float inaccuracy
            // Note* Doing (string)($number + 0) comparison because some mysql drivers doesn't truncate .00 decimals for floats

            switch ($transaction->type) {
                case Transaction::ONE_MONTH_SUBSCRIPTION:
                    if($transactionAmountWithoutTaxes === (string)($recipientUser->profile_access_price + 0)) {
                        $valid = true;
                    }
                    break;
                case Transaction::THREE_MONTHS_SUBSCRIPTION:
                    if($transactionAmountWithoutTaxes === (string)($recipientUser->profile_access_price_3_months * 3 + 0)) {
                        $valid = true;
                    }
                    break;
                case Transaction::SIX_MONTHS_SUBSCRIPTION:
                    if($transactionAmountWithoutTaxes === (string)($recipientUser->profile_access_price_6_months * 6 + 0)) {
                        $valid = true;
                    }
                    break;
                case Transaction::YEARLY_SUBSCRIPTION:
                    if($transactionAmountWithoutTaxes === (string)($recipientUser->profile_access_price_12_months * 12 + 0)) {
                        $valid = true;
                    }
                    break;
                case Transaction::POST_UNLOCK:
                    $post = Post::query()->where('id', $transaction->post_id)->first();
                    if((string)($post->price + 0) === $transactionAmountWithoutTaxes) {
                        $valid = true;
                    }
                    break;
                case Transaction::STREAM_ACCESS:
                    $stream = Stream::query()->where('id', $transaction->stream_id)->first();
                    if($stream && (string)($stream->price + 0) === $transactionAmountWithoutTaxes) {
                        $valid = true;
                    }
                    break;
                case Transaction::MESSAGE_UNLOCK:
                    $message = UserMessage::query()->where('id', $transaction->user_message_id)->first();
                    if((string)($message->price + 0) === $transactionAmountWithoutTaxes) {
                        $valid = true;
                    }
                    break;
                case Transaction::TIP_TYPE:
                case Transaction::CHAT_TIP_TYPE:
                case Transaction::DEPOSIT_TYPE:
                    $valid = true;
                    break;
            }
        }
        return $valid;
    }

    /**
     * Cancels a subscription
     * @param $subscription
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancelSubscription($subscription) {
        $cancelSubscription = false;

        if ($subscription->provider != null) {
            if ($subscription->provider === Transaction::PAYPAL_PROVIDER && $subscription->paypal_agreement_id != null) {
                $this->cancelPaypalAgreement($subscription->paypal_agreement_id);
                $cancelSubscription = true;
            } elseif ($subscription->provider === Transaction::STRIPE_PROVIDER && $subscription->stripe_subscription_id != null) {
                $this->cancelStripeSubscription($subscription->stripe_subscription_id);
                $cancelSubscription = true;
            } elseif ($subscription->provider === Transaction::CCBILL_PROVIDER && $subscription->ccbill_subscription_id != null) {
                if($this->cancelCCBillSubscription($subscription->ccbill_subscription_id)){
                    $cancelSubscription = true;
                }
            } elseif($subscription->provider === Transaction::CREDIT_PROVIDER) {
                $cancelSubscription = true;
            }

            // handle cancel subscription
            if($cancelSubscription) {
                $subscription->status = Subscription::CANCELED_STATUS;
                $subscription->canceled_at = new \DateTime();

                $subscription->save();
            }
        }

        return $cancelSubscription;
    }

    /**
     * Generate Mercado transaction
     * @param $transaction
     * @return string|void
     */
    public function generateMercadoTransaction($transaction) {
        try {
            $this->initiateMercadoPagoSdk();
            $reference = self::generateMercadoUniqueTransactionToken($transaction);

            $preference = new Preference();
            $preference->external_reference = $reference;
            $preference->notification_url = route('mercado.payment.update');

            $item = new \MercadoPago\Item();
            $item->title = self::getPaymentDescriptionByTransaction($transaction);
            $item->quantity = 1;
            $item->unit_price = $transaction->amount;

            $preference->items = array($item);

            $preference->back_urls = array(
                "success" => route('payment.checkMercadoPaymentStatus')
            );
            $preference->auto_return = "approved";

            $preference->save();

            return $preference->init_point;
        } catch (\Exception $exception) {
            $this->redirectByTransaction($transaction);
        }
    }

    /**
     * Verify Mercado transaction and update transaction accordingly
     * @param $reference
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function verifyMercadoTransaction($paymentId) {
        $transaction = null;
        try {
            $this->initiateMercadoPagoSdk();
            $mercadoPayment = \MercadoPago\Payment::get($paymentId);
            if($mercadoPayment) {
                $transaction = Transaction::query()->where('mercado_payment_token', $mercadoPayment->external_reference)->first();
                if($transaction && $transaction->status !== Transaction::APPROVED_STATUS) {
                    $success = $mercadoPayment->status === 'approved';
                    if($success) {
                        $transaction->status = Transaction::APPROVED_STATUS;
                        $transaction->mercado_payment_id = $paymentId;
                        $transaction->save();

                        $this->creditReceiverForTransaction($transaction);
                        NotificationServiceProvider::createTipNotificationByTransaction($transaction);
                        NotificationServiceProvider::createPPVNotificationByTransaction($transaction);
                    }
                }
            }
        } catch (\Exception $exception) {
            Log::error("Failed verifying Mercado transaction: " . $exception->getMessage());
        }

        return $transaction;
    }

    /**
     * Generates MercadoPago unique transaction token
     * @param $transaction
     * @return \Ramsey\Uuid\Type\Hexadecimal
     */
    private function generateMercadoUniqueTransactionToken($transaction)
    {
        // generate unique token for transaction
        do {
            $id = Uuid::uuid4()->getHex();
        } while (Transaction::query()->where('paystack_payment_token', $id)->first() != null);
        $transaction->mercado_payment_token = $id;

        return $id;
    }

    /**
     * Initiates MercadoPago SDK
     * @return void
     */
    private function initiateMercadoPagoSdk() {
        SDK::setAccessToken(getSetting('payments.mercado_access_token'));
    }

    /**
     * Generates Suitpay unique transaction token
     * @param $transaction
     * @return \Ramsey\Uuid\Type\Hexadecimal
     */
    private function generateSuitPayUniqueTransactionToken($transaction)
    {
        // generate unique token for transaction
        do {
            $id = Uuid::uuid4()->getHex();
        } while (Transaction::query()->where('suitpay_payment_token', $id)->first() != null);
        $transaction->suitpay_payment_token = $id;

        return $id;
    }

    private function setSuitpayPaymentDataToSession($transaction)
    {
        // collect suitpay payment data
        $sessionData = [
            'user_id' => auth()->user()->id,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'transaction_id' => $transaction->id,
            'suitpay_payment_code' => $transaction->suitpay_payment_code,
            'suitpay_payment_transaction_id' => $transaction->suitpay_payment_transaction_id,
            'suitpay_payment_token' => $transaction->suitpay_payment_token,
        ];

        // store suitpay payment data in session
        return Session::put('suitpay_payment_data', $sessionData);
    }


    /**
     * Generates Suitpay Subscription by transaction
     */
    public function generateSuitpayPaymentSubscriptionByTransaction($transaction)
    {
        $existingSubscription = $this->getSubscriptionBySenderAndReceiverAndProvider(
            $transaction['sender_user_id'],
            $transaction['recipient_user_id'],
            Transaction::SUITPAY_PROVIDER
        );

        if ($existingSubscription != null) {
            $subscription = $existingSubscription;
        } else {
            $subscription = $this->createSubscriptionFromTransaction($transaction);
        }
        $subscription['amount'] = $transaction['amount'];
        $subscription['expires_at'] = new \DateTime('+' . PaymentsServiceProvider::getSubscriptionMonthlyIntervalByTransactionType($transaction->type) . ' months', new \DateTimeZone('UTC'));
        $subscription['status'] = Subscription::ACTIVE_STATUS;
        $transaction['status'] = Transaction::APPROVED_STATUS;

        $subscription->save();

        // only send the notification for new subs
        if ($existingSubscription === null) {
            NotificationServiceProvider::createNewSubscriptionNotification($subscription);
        }
        $transaction['subscription_id'] = $subscription['id'];

        return $subscription;
    }
    public function getCouponDetails($couponCode)
    {
        // Lógica para buscar os dados do cupom
        $coupon = Coupon::where('coupon_code', $couponCode)->first();
        
        if ($coupon) {
            // Verifica o tipo de desconto e faz a conversão para reais se for 'fixed'
            $discountValue = $coupon->discount_type === 'percent' 
                             ? $coupon->discount_percent * 100 // Para percentagem, multiplica por 100
                             : $coupon->amount_off / 100;      // Para desconto fixo, divide por 100 para converter de centavos para reais
            
            return [
                'discount' => [
                    'type'  => $coupon->discount_type,
                    'value' => $discountValue,
                ],
            ];
        }
    
        return null; // Retorna null se o cupom não for encontrado ou não for válido
    }
    

}


