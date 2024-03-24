<?php

namespace App\Events;

use App\Transaction;
use Illuminate\Queue\SerializesModels;

class SalesOrderDue
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Transaction  $transaction
     * @return void
     */
    public function __construct(public Transaction $transaction)
    {
    }
}
