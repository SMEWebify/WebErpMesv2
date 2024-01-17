<?php

namespace App\Providers;

use App\Events\OrderLineUpdated;
use App\Events\DeliveryLineUpdated;
use App\Listeners\CheckOrderStatus;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use App\Listeners\UpdateDeliveryStatus;
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
            CheckOrderStatus::class,
        ],
        DeliveryLineUpdated::class => [
            UpdateDeliveryStatus::class,
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
