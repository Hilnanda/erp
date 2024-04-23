<?php

namespace App\Listeners;

use App\Events\SalesOrderCreated;
use App\Utils\TransactionUtil;

class OnSalesOrderCreated
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
    public function handle(SalesOrderCreated $event)
    {
        $transaction = $event->transaction;

        $this->transactionUtil->whatsappNotifySalesCreated($transaction);
    }
}
