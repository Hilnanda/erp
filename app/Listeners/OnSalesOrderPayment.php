<?php

namespace App\Listeners;

use App\Events\SalesOrderPayment;
use App\Utils\TransactionUtil;

class OnSalesOrderPayment
{
    /**
     * Constructor
     *
     * @param  TransactionUtil  $transactionUtil
     * @return void
     */
    public function __construct(protected TransactionUtil $transactionUtil)
    {
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(SalesOrderPayment $event)
    {
        $transaction = $event->transaction;
        $payment = $event->payment;
        $business_id = $event->business_id;

        $response = $this->transactionUtil->whatsappNotifySalesPayment($transaction, $payment, $business_id);

        $this->transactionUtil->activityLog($transaction, 'whatsapp_notification', null, $response);
    }
}