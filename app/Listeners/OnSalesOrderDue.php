<?php

namespace App\Listeners;

use App\Events\SalesOrderDue;
use App\Utils\TransactionUtil;

class OnSalesOrderDue
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
    public function handle(SalesOrderDue $event)
    {
        $transaction = $event->transaction;

        $this->transactionUtil->whatsappNotifySalesDue($transaction);
    }
}
