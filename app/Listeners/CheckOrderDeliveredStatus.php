<?php

namespace App\Listeners;

use App\Models\Workflow\Orders;
use App\Events\OrderLineUpdated;
use App\Models\Workflow\OrderLines;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckOrderDeliveredStatus
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
        
        #1 = Not delivered
        #2 = Partly delivered
        #3 = Delivered
        $allLinesDelivered = OrderLines::where('orders_id', $orderId)
            ->where('delivery_status', '<>', 3) // Non livré
            ->doesntExist();
        
        #1 = Open
        #2 = In progress
        #3 = Delivered
        #4 = Partly delivered
        if ($allLinesDelivered) {
            Orders::where('id', $orderId)->update(['statu' => 3]);
        }
        else{
            
            Orders::where('id',$orderId)->update(['statu'=> 4]);
        }
    }
}
