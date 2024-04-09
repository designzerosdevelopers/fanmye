<?php

namespace App\Console\Commands;
use Carbon\Carbon;
use App\Model\Wallet;
use App\Model\Transaction;
use App\Model\Subscription;
use App\Helpers\PaymentHelper;
use Illuminate\Console\Command;

class WalletUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'corn:UpdateWallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform hourly task';

    protected $paymentHandler;

    public function __construct(PaymentHelper $paymentHandler)
    {
        $this->paymentHandler = $paymentHandler;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
      //////////////////////Pending amount and wallet update code starting//////////////////////////
      $currentDateTime = Carbon::now();
      $eightDaysAgo = $currentDateTime->subDays(8);


       $transaction8new = Transaction::where('created_at', '>', $eightDaysAgo)->get();

        $pendingAmounts = [];

        foreach ($transaction8new as $transaction) {
            $userId = $transaction->recipient_user_id;
            $amount = $transaction->amount;
            
            if (!isset($pendingAmounts[$userId])) {
                $pendingAmounts[$userId] = 0;
            }
            
            $pendingAmounts[$userId] += $amount;
        }

        foreach ($pendingAmounts as $userId => $pendingAmount) {
            Wallet::where('user_id', $userId)->update(['pendingBalance' => $pendingAmount]);
        }



         $transaction8old = Transaction::where('pending', 1)->where('created_at', '<=', $eightDaysAgo)->get();
        $totalAmounts = [];
    
        foreach ($transaction8old as $transaction) {
            $userId = $transaction->recipient_user_id;
            $amount = $transaction->amount;
            
            if (!isset($totalAmounts[$userId])) {
                $totalAmounts[$userId] = 0;
            }
            
            $totalAmounts[$userId] += $amount;
        }
    
        foreach ($totalAmounts as $userId => $totalAmount) {
            Wallet::where('user_id', $userId)->increment('total', $totalAmount);
        }
    

      Transaction::where('pending', 1)->where('created_at', '<=', $eightDaysAgo)->update(['pending' => 0]);



       $usersWithOutPending = Transaction::where('pending', 0)
        ->distinct('recipient_user_id')
        ->pluck('recipient_user_id')
        ->toArray();
 
       $usersWithPending = Transaction::where('pending', 1)
        ->distinct('recipient_user_id')
        ->pluck('recipient_user_id')
        ->toArray();


        $usersWithOutPending = array_diff($usersWithOutPending, $usersWithPending);
    
        if (!empty($usersWithOutPending)) {
            Wallet::whereIn('user_id', $usersWithOutPending)->update(['pendingBalance' => 0]);
        }
        
        $this->info('Wallet Update Hourly task executed successfully.');
      //////////////////////Pending amount and wallet update code ending/////////////////

      //////////////////////Recuring Subscription code starting//////////////////////////
      
        $subscribers = Subscription::whereNull('canceled_at')->get();

        foreach ($subscribers as $subscriber) {

            $transaction = new Transaction();
            $transaction['sender_user_id'] = $subscriber->sender_user_id;
            $transaction['recipient_user_id'] = $subscriber->recipient_user_id;
            $transaction['type'] = $subscriber->type;
            $transaction['status'] = Transaction::APPROVED_STATUS;
            $transaction['amount'] = $subscriber->amount;
            $transaction['currency'] = config('app.site.currency_code');
            $transaction['payment_provider'] = 'paywithwallet';
            $transaction['subscription_id'] = $subscriber->id;

            
            if (Carbon::now()->gte(Carbon::parse($subscriber->expires_at))) {

                $wallet = Wallet::where('user_id', $subscriber->sender_user_id)->firstOrfail();
            
                if ($wallet->total >= $subscriber->amount) {

                    $this->paymentHandler->updateCreditSubscriptionByTransaction($transaction);
                    $transaction->save();
                    
                    $creditToDeduct = min($wallet->total, $subscriber->amount);
                    $wallet->total -= $creditToDeduct;
                    $wallet->save();
                } else {
                    $subscriber = Subscription::where('recipient_user_id', $transaction['recipient_user_id'])
                    ->where('sender_user_id', $transaction['sender_user_id'])
                    ->first();
                    $subscriber->status = Subscription::EXPIRED_STATUS;
                    $subscriber->save();
                }
            }
        }

        //////////////////////Recuring Subscription code ending//////////////////////////
        
    }
}
