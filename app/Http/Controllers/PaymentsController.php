<?php

namespace App\Http\Controllers;

use App\Helpers\PaymentHelper;
use App\Http\Requests\CreateTransactionRequest;
use App\Model\Subscription;
use App\Model\Transaction;
use App\Model\Wallet;
use App\Providers\InvoiceServiceProvider;
use App\Providers\NotificationServiceProvider;
use App\Providers\PaymentRequestServiceProvider;
use App\Providers\EmailsServiceProvider;
use App\Providers\PaymentsServiceProvider;
use App\Providers\PostsHelperServiceProvider;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Stripe\StripeClient;
use Yabacon\Paystack;
use App\Services\Gwapi;
use Carbon\Carbon;
use App\Model\Link; 
use App\Model\Visitor; 


class PaymentsController extends Controller
{
    protected $paymentHandler;
    private $gw;
    /**
     * PaymentsController constructor.
     * @param PaymentsServiceProvider $paymentsProvider
     */
    public function __construct(PaymentHelper $paymentHandler, Gwapi $gw)
    {
        $this->paymentHandler = $paymentHandler;
        $this->gw = $gw;
        
    }

    
    public function PayNMI(Request $request)    {
        // Use $this->gw instead of creating a new instance
        $this->updateUserBillingDetails($request);
        $this->gw->setLogin(env('NMI_API_KEY'));
        $this->gw->setBilling($request->firstName, $request->lastName, "Acme, Inc.", $request->address, "Suite 200", $request->city, $request->stateProvince, $request->zipCode, $request->country, "555-555-5555", "555-555-5556", "support@example.com", "www.example.com");
        $response = $this->gw->doSale($request->amount.".00", $request->cardNumber, $request->expirationDate, $request->cvv);

        //  $this->gw->setBilling("saqib", "ali", "Acme, Inc.", "123 Main St", "Suite 200", "Beverly Hills", "CA", "90210", "US", "555-555-5555", "555-555-5556", "support@example.com", "www.example.com");

        //  $this->gw->doSale("28.00", "4111111111111111", "1010");

           if($this->gw->Response() == 'SUCCESS' || $this->gw->Response() == 'Approved'){
                $transaction = new Transaction();
                $transaction['sender_user_id'] = Auth::user()->id;
                $transaction['recipient_user_id'] = Auth::user()->id;
                $transaction['type'] = 'Deposit';
                $transaction['status'] = 'approved';
                $transaction['amount'] = $request->amount;
                $transaction['currency'] = config('app.site.currency_code');
                $transaction['payment_provider'] = Transaction::CREDITCARD_PROVIDER;
                $transaction['pending'] = 0;
                $errorMessage = __('Something went wrong with this transaction. Please try again');
                $transaction->save();
                Wallet::where('user_id', $transaction['sender_user_id'])->increment('total',  $transaction['amount']);
                    EmailsServiceProvider::sendGenericEmail([
                        'email' => Auth::user()->email,
                        'subject' => 'Your Transaction is Processed for ' . $transaction['type'],
                        'title' => __('Hello, :name,', ['name' => Auth::user()->name]),
                        'content' => __('Dear User,
                    
                        I hope this message finds you well.
                        
                        We are writing to confirm that we have successfully received the deposit you made on Fanmye. We sincerely appreciate your contribution and participation on our platform.
                        
                        Confirmation of Deposit:
                        We have processed the deposit, and the amount has been credited to your account on Fanmye. Below, you will find the deposit details:
                        
                        Deposit Date: ' . \Carbon\Carbon::now()->format('F j, Y') . '
                        Amount Deposited: ' . $transaction['amount'] . ' ' . $transaction['currency'] . '
                        
                        Should you have any questions or concerns regarding this deposit or your account balance, please do not hesitate to contact our support team at support@fanmye.com. We are here to assist you with any inquiries you may have.
                        
                        At Fanmye, we are committed to providing exceptional service to all our users. If you require any assistance or have any questions, our support team is ready to help. Please feel free to reach out to them at support@fanmye.com, and they will be more than happy to assist you.
                        
                        Once again, we thank you for your deposit and your continued support of Fanmye. Your contributions help us maintain the quality of our platform and services for our community.
                        
                        Best Regards,
                        
                        The Fanmye Team
                        support@fanmye.com
                        Fanmye.com'
                    ),
                    'button' => [
                        'text' => __('Wallet'),
                        'url' => route('my.settings', ['type' => 'wallet']),
                    ]]);

            }
        return response()->json([
            'payresponse' => $this->gw->Response()
        ], 200);
    }



    public function paymentInitiateValidator(CreateTransactionRequest $request){
        return response()->json([
            'status' => 200
        ], 200);
    }



   public function payByWallet(Request $request){

        
        $transactionType = $request->get('transaction_type');

        $transaction = new Transaction();
        $transaction['sender_user_id'] = Auth::user()->id;
        $transaction['recipient_user_id'] = $request->get('recipient_user_id');
        $transaction['post_id'] = $request->get('post_id');
        $transaction['user_message_id'] = $request->get('user_message_id');
        $transaction['type'] = $transactionType;
        $transaction['status'] = Transaction::APPROVED_STATUS;
        $transaction['amount'] = $request->get('amount');
        $transaction['currency'] = config('app.site.currency_code');
        $transaction['payment_provider'] = $request->get('provider');
        $transaction['taxes'] = $request->get('taxes');
        $transaction['stream_id'] = $request->get('stream');
        $errorMessage = __('Something went wrong with this transaction. Please try again');

            $userAvailableAmount = $this->paymentHandler->getLoggedUserAvailableAmount();
            // check if user have enough money to pay with credit for this transaction

            if ($userAvailableAmount < $transaction['amount']) {
                $errorMessage = __("You don't have enough money to pay with credit for this transaction. Please try with another payment method");
                return $this->paymentHandler->redirectByTransaction($transaction, $errorMessage);
            } 

            $recipientUser = User::query()->where('id', $transaction['recipient_user_id'])->first();

        switch ($transactionType) {
            case Transaction::TIP_TYPE:
            case Transaction::CHAT_TIP_TYPE:
            case Transaction::STREAM_ACCESS:
            case Transaction::POST_UNLOCK:
            case Transaction::MESSAGE_UNLOCK:
                $userId = Auth::user()->id;
                $postId = (int) $transaction['post_id'];
                $streamId = (int) $transaction['stream_id'];
                $messageId = $transaction['user_message_id'];
                if($recipientUser->id === $transaction['sender_user_id']) {
                    return $this->paymentHandler->redirectByTransaction(
                        $transaction,
                        $errorMessage = __('Cannot pay to yourself.')
                    );
                }

                if($transactionType === Transaction::POST_UNLOCK && PostsHelperServiceProvider::userPaidForPost($userId, $postId)){
                    
                    return $this->paymentHandler->redirectByTransaction(
                        $transaction,
                        $errorMessage = __('You already unlocked this post.')
                    );
                } elseif($transactionType === Transaction::STREAM_ACCESS && PostsHelperServiceProvider::userPaidForStream($userId, $streamId)){
                    return $this->paymentHandler->redirectByTransaction(
                        $transaction,
                        $errorMessage = __('You already paid for this streaming')
                    );
                } elseif($transactionType === Transaction::MESSAGE_UNLOCK && PostsHelperServiceProvider::userPaidForMessage($userId, $messageId)){
                    return $this->paymentHandler->redirectByTransaction(
                        $transaction,
                        $errorMessage = __('You already paid access for this message')
                    );
                }

                 break;
                case Transaction::ONE_MONTH_SUBSCRIPTION:
                case Transaction::THREE_MONTHS_SUBSCRIPTION:
                case Transaction::SIX_MONTHS_SUBSCRIPTION:
                case Transaction::YEARLY_SUBSCRIPTION:
                if($recipientUser->id === $transaction['sender_user_id']) {
                    return $this->paymentHandler->redirectByTransaction(
                        $transaction,
                        $errorMessage = __('Cannot subscribe to yourself.')
                    );
                }

                $visitor = Visitor::where('ip_address', $request->ip())->first();

                if ($visitor) {
                    $lastUpdated = Carbon::parse($visitor->updated_at);
                    $currentTime = Carbon::now();
        
                        if ($lastUpdated->diffInHours($currentTime) <= 24) {
                            Link::where('id', $visitor->link_id)->increment('subscriber');
                        }
                }
                if($request->provider != "paywithwallet" )
                {
                    if (PostsHelperServiceProvider::hasActiveSub($transaction['sender_user_id'], $transaction['recipient_user_id'])) {
                        
                        $errorMessage = __('You already have an active subscription for this user.');
    
                        return $this->paymentHandler->redirectByTransaction($transaction, $errorMessage);
                    }
                }
                if ($transaction['payment_provider'] == "paywithwallet") {
                    $this->paymentHandler->generateCreditSubscriptionByTransaction($transaction);
                } elseif ($transaction['payment_provider'] == Transaction::CCBILL_PROVIDER) {
                    $redirectLink = $this->paymentHandler->generateCCBillSubscriptionPayment($transaction);
                }
                
                break;
            default:
                return $this->paymentHandler->redirectByTransaction($transaction);
        }

        
        $transaction->save();
        
        if ($transaction['payment_provider'] === "paywithwallet"
                && $transaction['status'] === Transaction::APPROVED_STATUS) {

                $this->paymentHandler->creditReceiverForTransaction($transaction);
                $this->paymentHandler->deductMoneyFromUserWalletForCreditTransaction($transaction, Auth::user()->wallet);
                $this->paymentHandler->createNewTipNotificationForCreditTransaction($transaction);
                NotificationServiceProvider::createPPVNotificationByTransaction($transaction);
        }
        
        
           // create payment request for this transaction and leave it on initiated status
        if($transaction['payment_provider'] === Transaction::MANUAL_PROVIDER){
            $manualPaymentFiles = $request->get('manual_payment_files');
            $manualPaymentDescription = $request->get('manual_payment_description');
            PaymentRequestServiceProvider::createDepositPaymentRequestByTransaction($transaction, $manualPaymentFiles, $manualPaymentDescription);
        }
    
    
    
            if ($transaction != null) {
            try {
                $invoice = InvoiceServiceProvider::createInvoiceByTransaction($transaction);
                if ($invoice != null) {
                    $transaction->invoice_id = $invoice->id;
                    $transaction->save();
                }
            } catch (\Exception $exception) {
                Log::error("Failed generating invoice for transaction: ".$transaction->id." error: ".$exception->getMessage());
            }
        }

        return $this->paymentHandler->redirectByTransaction($transaction);

    }



    /**
     * Initiates the payment based on the required provider.
     * @param CreateTransactionRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function initiatePayment(CreateTransactionRequest $request)
    {

        $transactionType = $request->get('transaction_type');
        $redirectLink = null;
        // generate one time transaction
        try {
            $this->updateUserBillingDetails($request);

            $transaction = new Transaction();
            $transaction['sender_user_id'] = Auth::user()->id;
            $transaction['recipient_user_id'] = $request->get('recipient_user_id');
            $transaction['post_id'] = $request->get('post_id');
            $transaction['user_message_id'] = $request->get('user_message_id');
            $transaction['type'] = $transactionType;
            $transaction['status'] = Transaction::INITIATED_STATUS;
            $transaction['amount'] = $request->get('amount');
            $transaction['currency'] = config('app.site.currency_code');
            $transaction['payment_provider'] = $request->get('provider');
            $transaction['taxes'] = $request->get('taxes');
            $transaction['stream_id'] = $request->get('stream');
            $errorMessage = __('Something went wrong with this transaction. Please try again');

            $recipientUser = User::query()->where('id', $transaction['recipient_user_id'])->first();
            if ($transaction['amount'] <= 0 || (!$recipientUser && $transactionType !== Transaction::DEPOSIT_TYPE)) {
                return $this->paymentHandler->redirectByTransaction($transaction, $errorMessage);
            }

            if(!$this->paymentHandler->validateTransaction($transaction, $recipientUser)) {
                return $this->paymentHandler->redirectByTransaction($transaction, $errorMessage);
            }

            if ($transaction['payment_provider'] == Transaction::PAYPAL_PROVIDER) {
                $this->paymentHandler->initiatePaypalContext();
            }

            if ($transaction['payment_provider'] == Transaction::CREDITCARD_PROVIDER) {

                $this->gw->setLogin(env('NMI_API_KEY'));
                $this->gw->setBilling($request->first_name, $request->last_name, "Acme, Inc.", $request->billing_address, "Suite 200", $request->city, $request->state, $request->postcode, $request->country, "555-555-5555", "555-555-5556", "support@example.com", "www.example.com");
                $this->gw->doSale($request->amount, $request->card_number, $request->expire_date, $request->cardcvv);
                $transaction['status'] = Transaction::APPROVED_STATUS;
                if($this->gw->Response() == 'SUCCESS' || $this->gw->Response() == 'Approved'){
                    
                }else{
                    
                    $transaction['status'] = Transaction::DECLINED_STATUS;
                    return $this->paymentHandler->redirectByTransaction($transaction, $errorMessage = $this->gw->Response());
                }


            }

            if (in_array($transaction['payment_provider'], [Transaction::STRIPE_PROVIDER, Transaction::OXXO_PROVIDER])) {
                $redirectLink = $this->paymentHandler->generateStripeSessionByTransaction($transaction);
                if ($redirectLink == null) {
                    $transaction['status'] = Transaction::DECLINED_STATUS;
                    $transaction->save();
                    return $this->paymentHandler->redirectByTransaction($transaction, $errorMessage = __('Failed generating stripe session'));
                }
            }

            // if ($transaction['payment_provider'] == Transaction::CREDIT_PROVIDER) {
 
            //     $transaction['status'] = Transaction::APPROVED_STATUS;
            //     $userAvailableAmount = $this->paymentHandler->getLoggedUserAvailableAmount();
            //     // check if user have enough money to pay with credit for this transaction

            //     if ($userAvailableAmount < $transaction['amount']) {
            //         $errorMessage = __("You don't have enough money to pay with credit for this transaction. Please try with another payment method");
            //         return $this->paymentHandler->redirectByTransaction($transaction, $errorMessage);
            //     }
            // }

            
            switch ($transactionType) {
                case Transaction::TIP_TYPE:
                case Transaction::CHAT_TIP_TYPE:
                case Transaction::STREAM_ACCESS:
                case Transaction::POST_UNLOCK:
                case Transaction::MESSAGE_UNLOCK:
                    $userId = Auth::user()->id;
                    $postId = $transaction['post_id'];
                    $streamId = $transaction['stream_id'];
                    $messageId = $transaction['user_message_id'];
                    if($recipientUser->id === $transaction['sender_user_id']) {
                        return $this->paymentHandler->redirectByTransaction(
                            $transaction,
                            $errorMessage = __('Cannot pay to yourself.')
                        );
                    }

                    if($transactionType === Transaction::POST_UNLOCK && PostsHelperServiceProvider::userPaidForPost($userId, $postId)){
                        return $this->paymentHandler->redirectByTransaction(
                            $transaction,
                            $errorMessage = __('You already unlocked this post.')
                        );
                    } elseif($transactionType === Transaction::STREAM_ACCESS && PostsHelperServiceProvider::userPaidForStream($userId, $streamId)){
                        return $this->paymentHandler->redirectByTransaction(
                            $transaction,
                            $errorMessage = __('You already paid for this streaming')
                        );
                    } elseif($transactionType === Transaction::MESSAGE_UNLOCK && PostsHelperServiceProvider::userPaidForMessage($userId, $messageId)){
                        return $this->paymentHandler->redirectByTransaction(
                            $transaction,
                            $errorMessage = __('You already paid access for this message')
                        );
                    }

                    if ($transaction['payment_provider'] == Transaction::PAYPAL_PROVIDER) {
                        $redirectLink = $this->paymentHandler->initiateOneTimePaypalTransaction($transaction);
                    } elseif ($transaction['payment_provider'] == Transaction::CREDIT_PROVIDER) {
                        $this->paymentHandler->generateOneTimeCreditTransaction($transaction);
                    } elseif($transaction['payment_provider'] == Transaction::COINBASE_PROVIDER){
                        $redirectLink = $this->paymentHandler->generateCoinBaseTransaction($transaction);
                    } elseif($transaction['payment_provider'] == Transaction::NOWPAYMENTS_PROVIDER) {
                        $redirectLink = $this->paymentHandler->generateNowPaymentsTransaction($transaction);
                    } elseif($transaction['payment_provider'] == Transaction::CCBILL_PROVIDER) {
                        $redirectLink = $this->paymentHandler->generateCCBillOneTimePaymentTransaction($transaction);
                    } elseif($transaction['payment_provider'] == Transaction::PAYSTACK_PROVIDER) {
                        $redirectLink = $this->paymentHandler->generatePaystackTransaction($transaction, Auth::user()->email);
                    }
                    break;
                case Transaction::DEPOSIT_TYPE:
                    $transaction['recipient_user_id'] = Auth::user()->id;
                    if ($transaction['payment_provider'] == Transaction::PAYPAL_PROVIDER) {
                        $redirectLink = $this->paymentHandler->initiateOneTimePaypalTransaction($transaction);
                    } elseif($transaction['payment_provider'] == Transaction::COINBASE_PROVIDER){
                        $redirectLink = $this->paymentHandler->generateCoinBaseTransaction($transaction);
                    } elseif($transaction['payment_provider'] == Transaction::NOWPAYMENTS_PROVIDER) {
                        $redirectLink = $this->paymentHandler->generateNowPaymentsTransaction($transaction);
                    } elseif($transaction['payment_provider'] == Transaction::CCBILL_PROVIDER) {
                        $redirectLink = $this->paymentHandler->generateCCBillOneTimePaymentTransaction($transaction);
                    } elseif($transaction['payment_provider'] == Transaction::PAYSTACK_PROVIDER) {
                        $redirectLink = $this->paymentHandler->generatePaystackTransaction($transaction, Auth::user()->email);
                    }
                    break;
                case Transaction::ONE_MONTH_SUBSCRIPTION:
                case Transaction::THREE_MONTHS_SUBSCRIPTION:
                case Transaction::SIX_MONTHS_SUBSCRIPTION:
                case Transaction::YEARLY_SUBSCRIPTION:
                    if($recipientUser->id === $transaction['sender_user_id']) {
                        return $this->paymentHandler->redirectByTransaction(
                            $transaction,
                            $errorMessage = __('Cannot subscribe to yourself.')
                        );
                    }

                    $visitor = Visitor::where('ip_address', $request->ip())->first();

                    if ($visitor) {
                        $lastUpdated = Carbon::parse($visitor->updated_at);
                        $currentTime = Carbon::now();
            
                            if ($lastUpdated->diffInHours($currentTime) <= 24) {
                                Link::where('id', $visitor->link_id)->increment('subscriber');
                            }
                    }

                    if (PostsHelperServiceProvider::hasActiveSub($transaction['sender_user_id'], $transaction['recipient_user_id'])) {
                        $errorMessage = __('You already have an active subscription for this user.');

                        return $this->paymentHandler->redirectByTransaction($transaction, $errorMessage);
                    }

                    if ($transaction['payment_provider'] == Transaction::PAYPAL_PROVIDER) {
                        $redirectLink = $this->paymentHandler->generatePaypalSubscriptionByTransaction($transaction);
                    } elseif ($transaction['payment_provider'] == Transaction::STRIPE_PROVIDER) {
                        $this->paymentHandler->generateStripeSubscriptionByTransaction($transaction);
                    } elseif ($transaction['payment_provider'] == Transaction::CREDIT_PROVIDER) {
                        $this->paymentHandler->generateCreditSubscriptionByTransaction($transaction);
                    } elseif ($transaction['payment_provider'] == Transaction::CCBILL_PROVIDER) {
                        $redirectLink = $this->paymentHandler->generateCCBillSubscriptionPayment($transaction);
                    } elseif ($transaction['payment_provider'] == Transaction::CREDITCARD_PROVIDER) { 
                        $redirectLink = $this->paymentHandler->generateCreditCardSubscriptionByTransaction($transaction);
                    }
                    break;
                default:
                    return $this->paymentHandler->redirectByTransaction($transaction);
            }
            $transaction->save();

            if ($transaction['payment_provider'] === Transaction::CREDIT_PROVIDER
                && $transaction['status'] === Transaction::APPROVED_STATUS) {
                $this->paymentHandler->creditReceiverForTransaction($transaction);
                $this->paymentHandler->deductMoneyFromUserWalletForCreditTransaction($transaction, Auth::user()->wallet);
                $this->paymentHandler->createNewTipNotificationForCreditTransaction($transaction);
                NotificationServiceProvider::createPPVNotificationByTransaction($transaction);
            }

            if ($transaction['payment_provider'] === Transaction::CREDITCARD_PROVIDER
            &&  $transaction['status'] === Transaction::APPROVED_STATUS) {
            $this->paymentHandler->createNewTipNotificationForCreditCardTransaction($transaction);
            NotificationServiceProvider::createPPVNotificationByTransaction($transaction);
        }
            // create payment request for this transaction and leave it on initiated status
            if($transaction['payment_provider'] === Transaction::MANUAL_PROVIDER){
                $manualPaymentFiles = $request->get('manual_payment_files');
                $manualPaymentDescription = $request->get('manual_payment_description');
                PaymentRequestServiceProvider::createDepositPaymentRequestByTransaction($transaction, $manualPaymentFiles, $manualPaymentDescription);
            }

            if ($transaction != null) {
                try {
                    $invoice = InvoiceServiceProvider::createInvoiceByTransaction($transaction);
                    if ($invoice != null) {
                        $transaction->invoice_id = $invoice->id;
                        $transaction->save();
                    }
                } catch (\Exception $exception) {
                    Log::error("Failed generating invoice for transaction: ".$transaction->id." error: ".$exception->getMessage());
                }
            }
        } catch (\Exception $exception) {
            return Redirect::route('feed')
                ->with('error', __('Payment failed.'));
        }

        // Url generated successfully
        if (isset($redirectLink) && in_array($transaction['payment_provider'], Transaction::ALLOWED_PAYMENT_PROVIDERS)) {
            // redirect on payment provider checkout page
            return Redirect::away($redirectLink);
        }
        


        return $this->paymentHandler->redirectByTransaction($transaction);
    }

    /**
     * Handles the deposit request response.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function executePaypalPayment(Request $request)
    {

        // Get the payment ID before session clear
        $payment_id = $request->get('paymentId');

        // Checking for valid request
        if (empty($request->get('token'))) {
            return Redirect::route('my.settings', ['type' => 'deposit'])
                ->with('error', __('Looks like the payment process has been cancelled.')); // warning
        }

        // find paypal transaction and update it
        $transaction = Transaction::query()->where(['paypal_transaction_token' => $request->get('token')])->first();
        if ($transaction != null) {
            if ($transaction->type != null) {
                if ($this->paymentHandler->isSubscriptionPayment($transaction->type) && $transaction->subscription_id != null) {
                    $this->paymentHandler->executePaypalAgreementPayment($transaction);
                    $transaction->save();
                } else {
                    if (empty($request->get('PayerID'))) {
                        return $this->paymentHandler->redirectByTransaction($transaction);
                    }

                    $this->paymentHandler->executeOneTimePaypalPayment($request, $transaction, $payment_id);
                    $transaction->save();
                }
            }

            if ($transaction != null && $transaction->status === Transaction::APPROVED_STATUS
                && ($transaction->type === Transaction::TIP_TYPE || $transaction->type === Transaction::CHAT_TIP_TYPE)) {
                NotificationServiceProvider::createNewTipNotification($transaction);
            }
        }

        
        return $this->paymentHandler->redirectByTransaction($transaction);
    }

    /**
     * Stripe payment confirmation endpoint / webhook.
     */
    public function stripePaymentsHook()
    {
        app('debugbar')->disable();

        $endpoint_secret = getSetting('payments.stripe_webhooks_secret');
        $payload = @file_get_contents('php://input');
        if (isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
            $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        } else {
            // Invalid payload
            http_response_code(400);
            exit();
        }

        $event = null;
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        }
        Log::info('Stripe payload received. Proceeding with completing the payment & fulfill the order.');
        Log::debug($event);

        try {
            if ($event->type === 'checkout.session.completed') {
            // Payment is successful and the subscription is created.
            $session = $event->data->object;
            if ($session->id != null) {
                // don't update oxxo transactions here
                $oxxoTransaction = Transaction::query()->where(['stripe_session_id' => $session->id, 'payment_provider' => Transaction::OXXO_PROVIDER])->first();
                if(!$oxxoTransaction) {
                    $this->paymentHandler->updateTransactionByStripeSessionId($session->id);
                }
            }
            // Occurs whenever a customer's subscription ends.
            } elseif ($event->type === 'customer.subscription.deleted' && isset($event->data->object) && $event->data->object->id != null) {
                $subscription = Subscription::query()->where('stripe_subscription_id', $event->data->object->id)->first();
                if ($subscription != null) {
                    $subscription->status = Subscription::CANCELED_STATUS;

                    $subscription->update();
                }
            } elseif (($event->type === 'invoice.paid' || $event->type === 'invoice.payment_failed') && isset($event->data->object)) {
                $paymentSucceeded = $event->type === 'invoice.paid' ? true : false;
                $stripe = new StripeClient(getSetting('payments.stripe_secret_key'));
                $stripeInvoice = $stripe->invoices->retrieve($event->data->object->id);
                if ($stripeInvoice != null && $stripeInvoice->subscription) {
                    $stripeSub = $stripe->subscriptions->retrieve($stripeInvoice->subscription);
                    if ($stripeSub != null && $stripeSub->id != null) {
                        $subscription = Subscription::query()->where('stripe_subscription_id', $stripeSub->id)->first();
                        if ($subscription != null && isset($subscription->expires_at) && $subscription->expires_at < new \DateTime()) {
                            $this->paymentHandler->createSubscriptionRenewalTransaction($subscription, $paymentSucceeded, $event->data->object->id);
                            // update subscription expire date
                            if ($paymentSucceeded) {
                                $subscription->status = Subscription::ACTIVE_STATUS;
                                $date = new \DateTime();
                                $subscription->expires_at = $date->setTimestamp($stripeSub->current_period_end);
                            } else {
                                if ($subscription->expires_at <= new \DateTime()) {
                                    $subscription->status = Subscription::EXPIRED_STATUS;
                                } else {
                                    $subscription->status = Subscription::FAILED_STATUS;
                                }
                            }
                            $subscription->save();
                        }
                    }
                }
            } elseif ($event->type === 'charge.refunded' && isset($event->data->object) && $event->data->object->payment_intent != null) {
                $transaction = Transaction::query()->where('stripe_transaction_id', $event->data->object->payment_intent)->with('subscription')->first();
                if ($transaction) {
                    if($transaction->status === Transaction::APPROVED_STATUS){
                        $this->paymentHandler->deductMoneyFromUserForRefundedTransaction($transaction);
                    }

                    $transaction->status =Transaction::REFUNDED_STATUS;
                    $transaction->save();

                    if($transaction->subscription != null){
                        $transaction->subscription->status = Subscription::SUSPENDED_STATUS;
                        $transaction->subscription->expires_at = new \DateTime('now', new \DateTimeZone('UTC'));
                        $transaction->subscription->save();
                    }
                }
            // handles oxxo (or other stripe payment providers) related hooks
            } elseif(($event->type === 'checkout.session.async_payment_succeeded' || $event->type === 'checkout.session.async_payment_failed') && isset($event->data->object) && $event->data->object->id != null) {
                $this->paymentHandler->updateTransactionByStripeSessionId($event->data->object->id);
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        http_response_code(200);
    }

    /**
     * Gets stripe transaction status and redirects.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getStripePaymentStatus(Request $request)
    {
        $transaction = $this->paymentHandler->updateTransactionByStripeSessionId($request->get('session_id'));
        NotificationServiceProvider::createTipNotificationByTransaction($transaction);
        NotificationServiceProvider::createPPVNotificationByTransaction($transaction);

        return $this->paymentHandler->redirectByTransaction($transaction);
    }

    /**
     * Handles Coinbase payment execution
     * @param Request $request
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkAndUpdateCoinbaseTransaction(Request $request)
    {
        $coinbaseTransactionToken = $request->get('token');
        $transaction = Transaction::query()->where('coinbase_transaction_token', $coinbaseTransactionToken)->first();
        if ($transaction != null) {
            $this->paymentHandler->checkAndUpdateCoinbaseTransaction($transaction);
            $transaction->save();
        }
        NotificationServiceProvider::createTipNotificationByTransaction($transaction);
        NotificationServiceProvider::createPPVNotificationByTransaction($transaction);

        return $this->paymentHandler->redirectByTransaction($transaction);
    }

    /**
     * Handles Coinbase payments hook
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function coinbaseHook(Request $request){
        if(!getSetting('payments.coinbase_webhook_key')){
            return response()->json([
                'status' => 400
            ], 400);
        }

        $payload = json_decode($request->getContent(), true);
        $computedSignature = hash_hmac('sha256', $request->getContent(), getSetting('payments.coinbase_webhook_key'));

        // Validate the webhook signature
        if (hash_equals($computedSignature, $request->server('HTTP_X_CC_WEBHOOK_SIGNATURE'))) {
            Log::info("coinbase payload: ", [$payload]);
            if(isset($payload['event']) && isset($payload['event']['type']) && isset($payload['event']['data']) && isset($payload['event']['data']['id'])){
                if($payload['event']['type'] === 'charge:failed' || $payload['event']['type'] === 'charge:confirmed'){
                    $transaction = Transaction::query()->where('coinbase_charge_id', $payload['event']['data']['id'])->first();
                    if($transaction != null){
                        if($payload['event']['type'] === 'charge:failed'){
                            $transaction->status = Transaction::CANCELED_STATUS;
                            $transaction->save();
                        } else if ($payload['event']['type'] === 'charge:confirmed') {
                            $transaction->status = Transaction::APPROVED_STATUS;
                            $transaction->save();
                            $this->paymentHandler->creditReceiverForTransaction($transaction);
                            NotificationServiceProvider::createTipNotificationByTransaction($transaction);
                            NotificationServiceProvider::createPPVNotificationByTransaction($transaction);
                        }
                    }
                }
            }
        } else {
            Log::info('Coinbase signature validation failed.');

            return response()->json([
                'status' => 400
            ], 400);
        }

        return response()->json([
            'status' => 200
        ], 200);
    }

    /**
     * Paypal handling webhook method.
     *
     * @param Request $request
     */
    public function paypalPaymentsHook(Request $request)
    {
        try {
            $webhookContent = json_decode($request->getContent(), true);
            $eventType = $webhookContent['event_type'];
            $cancelStatuses = ['partially_refunded', 'refunded', 'denied'];
            $resourceContent = $webhookContent['resource'];

            Log::info('Paypal payload received. Proceeding with completing the payment & fulfill the order.');
            Log::debug($webhookContent);

            switch ($eventType) {
                case 'PAYMENT.SALE.COMPLETED':
                    // handle recurring payments (one month subscriptions)
                    if (array_key_exists('billing_agreement_id', $resourceContent) && ! empty($resourceContent['billing_agreement_id'])) {
                        $agreementId = $resourceContent['billing_agreement_id'];
                        $this->paymentHandler->verifyPayPalAgreement($agreementId, null, $resourceContent['id']);
                    // handle one time payments
                    } elseif (array_key_exists('parent_payment', $resourceContent) && ! empty($resourceContent['parent_payment']) && empty($resourceContent['state'])) {
                        $transaction = Transaction::query()->where('paypal_transaction_id', $resourceContent['parent_payment'])->first();
                        if ($transaction != null && $transaction->status == Transaction::INITIATED_STATUS) {
                            if ($resourceContent['state'] == 'completed') {
                                $transaction->status = Transaction::APPROVED_STATUS;
                            } elseif (in_array($resourceContent['state'], $cancelStatuses)) {
                                $transaction->status = Transaction::CANCELED_STATUS;
                            } elseif ($resourceContent['state'] == 'pending') {
                                $transaction->status = Transaction::PENDING_STATUS;
                            }

                            $transaction->save();

                            if ($transaction->status == Transaction::APPROVED_STATUS) {
                                $this->paymentHandler->creditReceiverForTransaction($transaction);
                            }
                        }
                    }
                    break;
                case 'BILLING.SUBSCRIPTION.EXPIRED':
                case 'BILLING.SUBSCRIPTION.CANCELLED':
                case 'BILLING.SUBSCRIPTION.SUSPENDED':
                    if (isset($resourceContent['id']) && $resourceContent['id'] != null && isset($resourceContent['state']) && $resourceContent['state'] != null) {
                        // find a subscription by this id
                        $subscription = Subscription::query()->where('paypal_agreement_id', $resourceContent['id'])->first();
                        if ($subscription != null) {
                            if ($resourceContent['state'] == 'Cancelled') {
                                $subscription->status = Subscription::CANCELED_STATUS;
                            } elseif ($resourceContent['state'] == 'Suspended') {
                                $subscription->status = Subscription::SUSPENDED_STATUS;
                            } elseif ($resourceContent['state'] == 'Expired') {
                                $subscription->status = Subscription::EXPIRED_STATUS;
                            }

                            $subscription->save();
                        }
                    }
                    break;
                case 'PAYMENT.SALE.REFUNDED':
                    if (array_key_exists('parent_payment', $resourceContent) && ! empty($resourceContent['parent_payment'])) {
                        $transaction = Transaction::query()->where('paypal_transaction_id', $resourceContent['parent_payment'])->with('subscription')->first();
                        if ($transaction) {
                            if($transaction->status === Transaction::APPROVED_STATUS){
                                $this->paymentHandler->deductMoneyFromUserForRefundedTransaction($transaction);
                            }

                            $transaction->status = Transaction::REFUNDED_STATUS;
                            $transaction->save();

                            if($transaction->subscription != null){
                                $transaction->subscription->status = Subscription::SUSPENDED_STATUS;
                                $transaction->subscription->expires_at = new \DateTime('now', new \DateTimeZone('UTC'));
                                $transaction->subscription->save();
                            }
                        }
                    }
                    break;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        http_response_code(200);
    }

    /**
     * Method used for saving user billing details.
     *
     * @param $request
     */
    public function updateUserBillingDetails($request)
    {

        $firstName = $request->get('first_name');
        $lastName = $request->get('last_name');
        $billingAddress = $request->get('billing_address');
        $country = $request->get('country');
        $city = $request->get('city');
        $state = $request->get('state');
        $postcode = $request->get('postcode');
        $card_number = $request->get('card_number');
        $expire_date = $request->get('expire_date');
        $cardcvv = $request->get('cardcvv');
        
        // update user billing details if they changed
        if ($firstName != null || $lastName != null || $billingAddress != null) {
            $loggedUser = Auth::user();

            if ($loggedUser != null) {
                $updateData = [];
                if ($firstName != null && $firstName != $loggedUser->first_name) {
                    $updateData['first_name'] = $firstName;
                }

                if ($lastName != null && $lastName != $loggedUser->last_name) {
                    $updateData['last_name'] = $lastName;
                }

                if ($billingAddress != null && $billingAddress != $loggedUser->billing_address) {
                    $updateData['billing_address'] = $billingAddress;
                }

                if ($country != null && $country != $loggedUser->country) {
                    $updateData['country'] = $country;
                }

                if ($state != null && $state != $loggedUser->state) {
                    $updateData['state'] = $state;
                }

                if ($city != null && $city != $loggedUser->city) {
                    $updateData['city'] = $city;

                }

                if ($postcode != null && $postcode != $loggedUser->postcode) {
                    $updateData['postcode'] = $postcode;

                }

                if ($card_number != null && $card_number != $loggedUser->card_number) {
                    $updateData['card_number'] = $card_number;

                }

                if ($expire_date != null && $expire_date != $loggedUser->expire_date) {
                    $updateData['expire_date'] = $expire_date;

                }

                if ($cardcvv != null && $cardcvv != $loggedUser->cardcvv) {
                    $updateData['cardcvv'] = $cardcvv;

                }
                if(!empty($updateData)) {
                    $loggedUser->update($updateData);
                }
            }
        }
    }

    /**
     * Handles NowPayments payment execution
     * @param Request $request
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkAndUpdateNowPaymentsTransaction(Request $request)
    {
        $nowPaymentsTransactionToken = $request->get('orderId');
        $transaction = null;
        if($nowPaymentsTransactionToken) {
            $transaction = Transaction::query()->where('nowpayments_order_id', $nowPaymentsTransactionToken)->first();
            if ($transaction) {
                $this->paymentHandler->checkAndUpdateNowPaymentsTransaction($transaction);
                $transaction->save();
            }
        }

        return $this->paymentHandler->redirectByTransaction($transaction);
    }

    /**
     * Process NowPayments IPN hooks
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nowPaymentsHook(Request $request){
        if(!getSetting('payments.nowpayments_ipn_secret_key')){
            Log::info("NowPayments hook error: missing IPN secret key");
            return response()->json([
                'status' => 400
            ], 400);
        }

        try{
            if (isset($_SERVER['HTTP_X_NOWPAYMENTS_SIG']) && !empty($_SERVER['HTTP_X_NOWPAYMENTS_SIG'])) {
                $received_hmac = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'];
                $request_json = $request->getContent();
                $payload = json_decode($request_json, true);
                Log::info("NowPayments hook received: ", [$payload]);
                ksort($payload);
                $sorted_request_json = json_encode($payload, JSON_UNESCAPED_SLASHES);
                if ($request_json !== false && !empty($request_json)) {
                    $hmac = hash_hmac("sha512", $sorted_request_json, trim(getSetting('payments.nowpayments_ipn_secret_key')));
                    if ($hmac == $received_hmac) {
                        Log::info("NowPayments hook payload: ", [$payload]);
                        if(isset($payload['order_id']) && isset($payload['payment_status']) && isset($payload['payment_id'])) {
                            $transaction = Transaction::query()->where('nowpayments_order_id', $payload['order_id'])->with('receiver')->first();
                            if($transaction){
                                if(in_array($transaction->status, [Transaction::INITIATED_STATUS, Transaction::PENDING_STATUS, Transaction::PARTIALLY_PAID_STATUS])){
                                    // payment approved
                                    if($payload['payment_status'] === 'finished') {
                                        $transaction->status = Transaction::APPROVED_STATUS;
                                        $this->paymentHandler->creditReceiverForTransaction($transaction);
                                        NotificationServiceProvider::createTipNotificationByTransaction($transaction);
                                        NotificationServiceProvider::sendApprovedDepositTransactionEmailNotification($transaction);
                                        NotificationServiceProvider::createPPVNotificationByTransaction($transaction);
                                        // payment pending
                                    } elseif ($transaction->status !== Transaction::PENDING_STATUS && in_array($payload['payment_status'], ['waiting', 'confirming', 'sending'])) {
                                        $transaction->nowpayments_payment_id = $payload['payment_id'];
                                        $transaction->status = Transaction::PENDING_STATUS;
                                        // payment partially paid
                                    } elseif ($payload['payment_status'] === 'partially_paid' && $transaction->status !== Transaction::PARTIALLY_PAID_STATUS) {
                                        $transaction->status = Transaction::PARTIALLY_PAID_STATUS;
                                        NotificationServiceProvider::sendNowPaymentsPartiallyPaidTransactionEmailNotification($transaction);
                                        // payment expired or failed
                                    } elseif (in_array($payload['payment_status'], ['expired', 'failed'])) {
                                        $transaction->status = Transaction::DECLINED_STATUS;
                                    }
                                    $transaction->save();
                                    // handle refund
                                } else if($transaction->status === Transaction::APPROVED_STATUS && $payload['payment_status'] === 'refunded') {
                                    $this->paymentHandler->deductMoneyFromUserForRefundedTransaction($transaction);
                                    $transaction->status = Transaction::REFUNDED_STATUS;
                                    $transaction->save();
                                }
                            }
                        }

                        return response()->json([
                            'status' => 200
                        ], 200);
                    } else {
                        Log::info('NowPayments HMAC signature does not match');
                    }
                } else {
                    Log::info('NowPayments Error reading POST data');
                }
            } else {
                Log::info('NowPayments No HMAC signature sent.');
            }
        } catch (\Exception $exception){
            Log::info("NowPayments hook error: ", [$exception->getMessage()]);
        }

        return response()->json([
            'status' => 400
        ], 400);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function processCCBillTransaction(Request $request)
    {

        $paymentToken = $request->get('token');
        $transaction = null;
        if($paymentToken) {
            $transaction = Transaction::query()->where('ccbill_payment_token', $paymentToken)->first();
        }

        return $this->paymentHandler->redirectByTransaction($transaction);
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function ccBillHook(Request $request)
    {
        $ccBillAccountNumber = $request->get('clientAccnum');
        $ccBillSubAccountNumber = $request->get('clientSubacc');
        $eventType = $request->get('eventType');

        try {
            // check if this webhook comes with the right ccbill account numbers
            if ($ccBillAccountNumber === getSetting('payments.ccbill_account_number')
                && ($ccBillSubAccountNumber === getSetting('payments.ccbill_subaccount_number_recurring')
                    || $ccBillSubAccountNumber === getSetting('payments.ccbill_subaccount_number_one_time'))) {
                $content = $request->getContent();
                // handles possible UTF8 incorrectly encoded characters coming from CCBill
                $utfEncodedContent = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                $eventBody = json_decode($utfEncodedContent, true, 512, JSON_THROW_ON_ERROR);
                Log::debug('CCBill hook received eventType: ' . $eventType);
                Log::debug('CCBill hook received: ', [$eventBody]);

                // handle payment success or failure
                if (isset($eventBody['X-token']) && in_array($eventType, ['NewSaleSuccess', 'NewSaleFailure'])) {
                    $transaction = Transaction::where('ccbill_payment_token', $eventBody['X-token'])->with('subscription')->first();
                    if ($transaction) {
                        $subscriptionId = isset($eventBody['subscriptionId']) ? $eventBody['subscriptionId'] : null;
                        $saleSuccess = $eventType === 'NewSaleSuccess' ? true : false;
                        $transaction->ccbill_transaction_id = isset($eventBody['transactionId']) ? $eventBody['transactionId'] : null;
                        $transaction->ccbill_subscription_id = $subscriptionId;
                        $transaction->status = $saleSuccess ? Transaction::APPROVED_STATUS : Transaction::DECLINED_STATUS;
                        $transaction->save();

                        if($this->paymentHandler->isSubscriptionPayment($transaction->type) && $transaction->subscription) {
                            $subscription = $transaction->subscription;
                            $subscription->ccbill_subscription_id = $subscriptionId;
                            if($saleSuccess) {
                                $expiresDate = new \DateTime('+'.$this->paymentHandler->getCCBillRecurringPeriodInDaysByTransaction($transaction).' days', new \DateTimeZone('UTC'));
                                if ($subscription->status != Subscription::ACTIVE_STATUS) {
                                    $subscription->status = Subscription::ACTIVE_STATUS;
                                    $subscription->expires_at = $expiresDate;

                                    NotificationServiceProvider::createNewSubscriptionNotification($subscription);
                                } else {
                                    $subscription->expires_at = $expiresDate;
                                }

                            } else {
                                $subscription->status = Subscription::FAILED_STATUS;
                            }
                            $subscription->save();
                        }


                        if ($transaction->status == Transaction::APPROVED_STATUS) {
                            $this->paymentHandler->creditReceiverForTransaction($transaction);
                            NotificationServiceProvider::createTipNotificationByTransaction($transaction);
                            NotificationServiceProvider::createPPVNotificationByTransaction($transaction);
                        }
                    }
                    // handle refund
                } elseif(isset($eventBody['transactionId']) && $eventType === 'Refund') {
                    $transaction = Transaction::where('ccbill_transaction_id', $eventBody['transactionId'])->with('subscription')->first();
                    if ($transaction->status === Transaction::APPROVED_STATUS) {
                        $this->paymentHandler->deductMoneyFromUserForRefundedTransaction($transaction);
                    }

                    $transaction->status = Transaction::REFUNDED_STATUS;
                    $transaction->save();

                    if ($transaction->subscription != null) {
                        $transaction->subscription->status = Subscription::SUSPENDED_STATUS;
                        $transaction->subscription->expires_at = new \DateTime('now', new \DateTimeZone('UTC'));
                        $transaction->subscription->save();
                    }
                    // handle renewal success / failure, cancellation or expiration
                } elseif ($eventBody['subscriptionId'] && in_array($eventType, ['RenewalSuccess', 'Renewal Failure', 'Cancellation', 'Expiration'])) {
                    $subscription = Subscription::where('ccbill_subscription_id', $eventBody['subscriptionId'])->first();
                    if ($subscription) {
                        if ($eventType === 'RenewalSuccess') {
                            $this->paymentHandler->createSubscriptionRenewalTransaction($subscription, $paymentSucceeded = true, $eventBody['subscriptionId']);
                            $expiresDate = new \DateTime($eventBody['renewalDate'], new \DateTimeZone('UTC'));
                            if ($subscription->status != Subscription::ACTIVE_STATUS) {
                                $subscription->status = Subscription::ACTIVE_STATUS;
                                $subscription->expires_at = $expiresDate;

                                NotificationServiceProvider::createNewSubscriptionNotification($subscription);
                            } else {
                                $subscription->expires_at = $expiresDate;
                            }
                        } elseif ($eventType === 'Renewal Failure') {
                            $subscription->status = Subscription::SUSPENDED_STATUS;
                        } elseif ($eventType === 'Cancellation') {
                            $subscription->status = Subscription::CANCELED_STATUS;
                            $subscription->cancelled_at = new \DateTime();
                        } elseif ($eventType === 'Expiration') {
                            $subscription->status = Subscription::EXPIRED_STATUS;
                        }

                        $subscription->save();
                    }
                }
            }
        } catch (\Exception $exception) {
            Log::debug('CCBill hook error:', [$exception->getMessage()]);
        }
    }

    /**
     * Verifies paystack payment by calling their API and updating transaction in our side
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyPaystackTransaction(Request $request) {
        $reference = $request->get('reference');
        $transaction = $this->paymentHandler->verifyPaystackTransaction($reference);

        return $this->paymentHandler->redirectByTransaction($transaction);
    }

    /**
     * @param Request $request
     * @return void
     */
    public function paystackHook(Request $request) {
        // Retrieve the request's body and parse it as JSON
        $event = Paystack\Event::capture();

        /* Verify that the signature matches one of your keys*/
        $my_keys = [
            'live'=>getSetting('payments.paystack_secret_key'),
            'test'=>getSetting('payments.paystack_secret_key'),
        ];
        $owner = $event->discoverOwner($my_keys);
        if(!$owner){
            return;
        }
        Log::debug('Paystack hook received: ', [$event]);

        switch($event->obj->event){
            // charge.success
            case 'charge.success':
                if('success' === $event->obj->data->status){
                    $this->paymentHandler->verifyPaystackTransaction($event->obj->data->reference);
                }
                break;
            case 'refund.processed':
                if($event->obj->data->transaction_reference) {
                    $transaction = Transaction::where('paystack_payment_token', $event->obj->data->transaction_reference)->first();
                    if($transaction->status === Transaction::APPROVED_STATUS){
                        $transaction->status = Transaction::REFUNDED_STATUS;
                        $transaction->save();
                        $this->paymentHandler->deductMoneyFromUserForRefundedTransaction($transaction);
                    }
                }

                break;
        }

        http_response_code(200);
    }
}
