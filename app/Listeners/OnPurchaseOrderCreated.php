<?php

namespace App\Listeners;

use App\Events\PurchaseOrderCreated;
use App\Utils\TransactionUtil;

class OnPurchaseOrderCreated
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
    public function handle(PurchaseOrderCreated $event)
    {
        $transaction = $event->transaction;

        $this->transactionUtil->whatsappNotifyPurchaseCreated($transaction);
    }
}
