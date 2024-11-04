<?php

namespace App\Console\Commands;

use App\Business;
use App\JobQueue;
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
     * The type of the console command.
     *
     * @var string
     */
    protected $type = 'autoSendSalesDueReminder';

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

            foreach ($businesses as $business) {
                $queue_ids = null;

                if (!$jobQueue = JobQueue::where('business_id', $business->id)
                    ->where('type', $this->type)
                    ->first()
                ) {
                    continue;
                }

                if (empty($jobQueue->ids) && !is_null($jobQueue->ids)) {
                    $now = now($business->time_zone);
                    $today = explode(' ', $now);
                    $today = \Carbon::parse($today[0] . ' 07:00:00');
                    $nextSchedule = $today->addDay();
                    $jobQueue->update([
                        'next_schedule_at' => $nextSchedule,
                        'ids' => null,
                    ]);
                    continue;
                }

                if ($jobQueue->next_schedule_at > now($business->id)) {
                    continue;
                }

                $queue_ids = $jobQueue->ids;

                if (empty($queue_ids)) {
                    $queue_ids = [];
                    $reminderDate = now($business->time_zone)->endOfDay()->addDays($due_in_days);

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
                        array_push($queue_ids, $sales->id);
                    }
                }

                $this->sendNotifications($queue_ids, $business);
            }
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            exit($e->getMessage());
        }
    }

    public function sendNotifications($queue_ids = [], $business) {
        $send_ids = array_splice($queue_ids, 0, env('REMINDER_DUE_INTERVAL', 10));

        JobQueue::updateOrCreate([
            'business_id' => $business->id,
            'type' => $this->type,
        ],[
            'ids' => implode(',', $queue_ids),
            'next_schedule_at' => now($business->time_zone)->addMinutes(env('REMINDER_DUE_DELAY', 1)),
        ]);

        $salesList = Transaction::whereIn('id', $send_ids)->get();
        foreach ($salesList as $sales) {
            event(new \App\Events\SalesOrderDue($sales));
        }
    }
}
