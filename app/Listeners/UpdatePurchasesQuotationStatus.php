<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\PurchaseCreated;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Purchases\PurchaseQuotationLines;

class UpdatePurchasesQuotationStatus implements ShouldQueue
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
     *
     * @param  \App\Events\PurchaseCreated  $event
     * @return void
     */
    public function handle(PurchaseCreated $event)
    {
        // Retrieve the associated price request
        $PurchasesQuotation = $event->purchasesQuotation;
        // Retrieve all lines from the price request
        $PurchaseQuotationLines = PurchaseQuotationLines::where('purchases_quotation_id', $PurchasesQuotation->id)->get();

        // Check if all lines of the price request are converted or partially converted
        $allCreated = $PurchaseQuotationLines->every(function ($line) {
            return $line->qty_accepted >= $line->qty_to_order; // Check the quantity created for each line
        });

        // Update the status of the linked quote
        $purchaseQuotation = PurchasesQuotation::find($PurchasesQuotation->id);
        
        if ($purchaseQuotation) {
            if ($allCreated) {
                // If all lines are created, status 6 (PO Created)
                $purchaseQuotation->statu = 6;
            } else {
                // If only part of the lines are created, status 5 (PO partly created)
                $purchaseQuotation->statu = 5;
            }
            $purchaseQuotation->save();
        }
    }
}
