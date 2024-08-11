<?php

namespace App\Listeners;

use App\Models\Purchases\Purchases;
use App\Events\PurchaseReceiptCreated;
use App\Models\Purchases\PurchaseLines;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Purchases\PurchaseReceiptLines;

class UpdatePurchaseStatus
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
    public function handle(PurchaseReceiptCreated $event)
    {
        // Retrieve the reception lines associated with the reception
        $purchaseReceiptLines = PurchaseReceiptLines::where('purchase_receipt_id', $event->purchaseReceipt->id)->get();

        // For each receipt line, update the associated purchase
        foreach ($purchaseReceiptLines as $line) {
            $purchase = Purchases::find($line->purchaseLines->purchases_id);

            if ($purchase) {
                // Retrieve all lines associated with this purchase order
                $purchaseLines = PurchaseLines::where('purchases_id', $purchase->id)->get();

                // Check if all rows have 'receipt_qty' greater than or equal to 'qty'
                $allReceived = $purchaseLines->every(function ($purchaseLine) {
                    return $purchaseLine->receipt_qty >= $purchaseLine->qty;
                });

                // If all lines are received, update the purchase order status to 4
                if ($allReceived) {
                    $purchase->statu = 4;
                } else {
                   // If this is not the case, we make sure to set the status to 3
                    $purchase->statu = 3;
                }

                $purchase->save();
            }
        }
    }
}
