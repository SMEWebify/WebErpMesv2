<?php

namespace App\Listeners;

use App\Models\Workflow\Orders;
use App\Events\OrderLineUpdated;
use App\Models\Workflow\OrderLines;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckOrderStatus
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderLineUpdated $event)
    {

        $orderLine = OrderLines::find($event->orderLineId);
        $orderId = $orderLine->orders_id;

        $allLinesDelivered = OrderLines::where('orders_id', $orderId)
            ->where('delivery_status', '<>', 3) // Non livré
            ->doesntExist();
            

        if ($allLinesDelivered) {
            Orders::where('id', $orderId)->update(['statu' => 3]);
        }
        else{
            
            Orders::where('id',$orderId)->update(['statu'=> 4]);
        }
    }
}
