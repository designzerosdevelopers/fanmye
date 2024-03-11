<?php

namespace App\Console\Commands;
use Carbon\Carbon;
use App\Model\Wallet;
use App\Model\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
      
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
        Log::info('your corn job is working');
    }
}
