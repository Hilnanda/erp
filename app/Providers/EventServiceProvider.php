<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // 'App\Events\Event' => [
        //     'App\Listeners\EventListener',
        // ],
        \App\Events\TransactionPaymentAdded::class => [
            \App\Listeners\AddAccountTransaction::class,
        ],

        \App\Events\TransactionPaymentUpdated::class => [
            \App\Listeners\UpdateAccountTransaction::class,
        ],

        \App\Events\TransactionPaymentDeleted::class => [
            \App\Listeners\DeleteAccountTransaction::class,
        ],

        \App\Events\SalesOrderCreated::class => [
            \App\Listeners\OnSalesOrderCreated::class,
        ],

        \App\Events\PurchaseOrderCreated::class => [
            \App\Listeners\OnPurchaseOrderCreated::class,
        ],

        \App\Events\SalesOrderPayment::class => [
            \App\Listeners\OnSalesOrderPayment::class,
        ],

        \App\Events\SalesOrderDue::class => [
            \App\Listeners\OnSalesOrderDue::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {

        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
