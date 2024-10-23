<?php

namespace App\Console\Commands;

use App\Business;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoSendSalesDueReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simetris:autoSendSalesDueReminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends payment reminder to customers which have sales due in 3 days for payment reminder';

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
     * @return mixed
     */
    public function handle()
    {
        if (!env('SALES_DUE_NOTIFICATION', true)) {
            return;
        }
        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $businesses = Business::where('is_active', 1)->get();
            
            $due_in_days = (int) env('REMINDER_DUE_DAY', 3);
            $count = 0;
            foreach ($businesses as $business) {
                $reminderDate = now($business->time_zone)->startOfDay()->addDays($due_in_days);

                $salesList = Transaction::where('business_id', $business->id)
                    ->where('type', 'sell')
                    ->where('payment_status', '!=', 'paid')
                    ->whereRaw("IF(pay_term_type = 'days',
                        DATE(DATE_ADD(transaction_date, INTERVAL pay_term_number DAY)) <= DATE('" . $reminderDate ."'),
                        DATE(DATE_ADD(transaction_date, INTERVAL pay_term_number MONTH)) <= DATE('" . $reminderDate . "')
                        )")
                    ->get();

                foreach ($salesList as $sales) {
                    $dueDate = Carbon::parse($sales->due_date);
                    if (!!($dueDate->diffInDays($reminderDate) % $due_in_days)) {
                        continue;
                    }
                    event(new \App\Events\SalesOrderDue($sales));
                    $count++;
                    if ($count == env('REMINDER_DUE_INTERVAL', 10)) {
                        $count = 0;
                        sleep((int) env('REMINDER_DUE_DELAY', 60));
                    }
                }
            }

        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            exit($e->getMessage());
        }
    }
}
