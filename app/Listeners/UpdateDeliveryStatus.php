<?php

namespace App\Listeners;

use App\Models\Workflow\Deliverys;
use App\Events\DeliveryLineUpdated;
use App\Models\Workflow\DeliveryLines;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateDeliveryStatus
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
    public function handle(DeliveryLineUpdated $event)
    {
        $deliveryLine = DeliveryLines::find($event->deliveryLineId);
        $deliveryId = $deliveryLine->deliverys_id;

        #1 = Chargeable
        #2 = Not chargeable
        #3 = Partly invoiced
        #4 = Invoiced
        // Vérifiez si toutes les lignes de livraison sont facturées
        $allLinesInvoiced = DeliveryLines::where('deliverys_id', $deliveryId)
            ->where('invoice_status', '<>', 4) // Non facturé
            ->doesntExist();

        #1 = Chargeable
        #2 = Not chargeable
        #3 = Partly invoiced
        #4 = Invoiced
        if ($allLinesInvoiced) {
            // Mettez à jour le statut de facturation de la livraison
            Deliverys::where('id', $deliveryId)->update(['invoice_status' => 4]);
        }else{
            
            Deliverys::where('id',$deliveryId)->update(['invoice_status'=> 3]);
        }
    }
}
