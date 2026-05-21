<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\QuoteCreated;
use App\Events\PurchaseCreated;
use App\Events\TaskChangeStatu;
use App\Events\OrderLineUpdated;
use App\Events\QuoteStatusChanged;
use App\Events\DeliveryLineUpdated;
use App\Events\OrderStatusChanged;
use App\Events\InvoiceStatusChanged;
use App\Events\DeliveryStatusChanged;
use App\Listeners\NotifyAdminOnLockout;
use App\Listeners\SendWelcomeEmail;
use App\Events\PurchaseReceiptCreated;
use App\Listeners\UpdateCompanyStatus;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Registered;
use App\Listeners\UpdateDeliveryStatus;
use App\Listeners\MarkDeliveryLinesNotChargeableOnOrderCancel;
use App\Listeners\UpdatePurchaseStatus;
use App\Listeners\UpdateOpportunityStatus;
use App\Listeners\CheckOrderLineTaskStatus;
use App\Listeners\CheckOrderDeliveredStatus;
use App\Listeners\UpdateCompanyStatusForOrder;
use App\Listeners\UpdatePurchasesQuotationStatus;
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
            SendWelcomeEmail::class,
        ],
        Lockout::class => [
            NotifyAdminOnLockout::class,
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
        PurchaseCreated::class => [
            UpdatePurchasesQuotationStatus::class,
        ],
        PurchaseReceiptCreated::class => [
            UpdatePurchaseStatus::class,
        ],
        OrderStatusChanged::class    => [
            MarkDeliveryLinesNotChargeableOnOrderCancel::class,
        ],
        InvoiceStatusChanged::class  => [],
        DeliveryStatusChanged::class => [],
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
