<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\QuoteCreated;
use App\Events\TaskChangeStatu;
use App\Events\OrderLineUpdated;
use App\Events\QuoteStatusChanged;
use App\Events\DeliveryLineUpdated;
use Illuminate\Support\Facades\Event;
use App\Listeners\UpdateCompanyStatus;
use Illuminate\Auth\Events\Registered;
use App\Listeners\UpdateDeliveryStatus;
use App\Listeners\UpdateOpportunityStatus;
use App\Listeners\CheckOrderLineTaskStatus;
use App\Listeners\CheckOrderDeliveredStatus;
use App\Listeners\UpdateCompanyStatusForOrder;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        OrderLineUpdated::class => [
            CheckOrderDeliveredStatus::class,
        ],
        DeliveryLineUpdated::class => [
            UpdateDeliveryStatus::class,
        ],
        TaskChangeStatu::class => [
            CheckOrderLineTaskStatus::class,
        ],
        QuoteStatusChanged::class => [
            UpdateOpportunityStatus::class,
        ],
        QuoteCreated::class => [
            UpdateCompanyStatus::class,
        ],
        OrderCreated::class => [
            UpdateCompanyStatusForOrder::class,
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
}
