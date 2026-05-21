<?php

namespace App\Listeners;

use App\Services\DeliveryService;
use App\Events\DeliveryLineUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateDeliveryStatus implements ShouldQueue
{
    protected $deliveryService;

    /**
     * Create the event listener.
     */
    public function __construct(DeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    /**
     * Handle the event.
     */
    public function handle(DeliveryLineUpdated $event)
    {
        $this->deliveryService->recomputeInvoiceStatus($event->deliveryLine->deliverys_id);
    }
}
