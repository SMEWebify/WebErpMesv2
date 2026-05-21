<?php

namespace App\Listeners;

use App\Services\DeliveryService;
use App\Events\OrderStatusChanged;
use App\Models\Workflow\DeliveryLines;
use Illuminate\Contracts\Queue\ShouldQueue;

class MarkDeliveryLinesNotChargeableOnOrderCancel implements ShouldQueue
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
     * When an order is cancelled (statu = 6), any delivery line still waiting to
     * be invoiced (facturable = 1 or partiellement = 3) is set to "non facturable"
     * (2) so the BL no longer stays due to invoice indefinitely. Already invoiced
     * lines (4) are left untouched.
     *
     * @param OrderStatusChanged $event
     * @return void
     */
    public function handle(OrderStatusChanged $event)
    {
        // 6 = Canceled
        if ($event->newStatus !== 6) {
            return;
        }

        $orderId = $event->order->id;

        $lines = DeliveryLines::whereHas('OrderLine', fn ($q) => $q->where('orders_id', $orderId))
            ->whereIn('invoice_status', [1, 3])
            ->get(['id', 'deliverys_id']);

        if ($lines->isEmpty()) {
            return;
        }

        DeliveryLines::whereIn('id', $lines->pluck('id'))
            ->update(['invoice_status' => 2]); // Non facturable

        $lines->pluck('deliverys_id')->unique()->each(
            fn ($deliveryId) => $this->deliveryService->recomputeInvoiceStatus($deliveryId)
        );
    }
}
