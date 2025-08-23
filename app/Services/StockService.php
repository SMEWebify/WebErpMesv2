<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Events\OrderLineUpdated;
use App\Models\Products\StockMove;
use App\Models\Workflow\OrderLines;
use App\Models\Purchases\PurchaseReceiptLines;

class StockService
{
    /**
     * Create a new stock move record.
     *
     * This method creates a new stock move record in the database using the provided data.
     * The data array should contain the necessary fields to create a StockMove record.
     *
     * @param array $data The data to create the stock move record.
     * @return \App\Models\Products\StockMove The created stock move record.
     */
    public function createStockMove(array $data)
    {
        return StockMove::create($data);
    }

    /**
     * Update the quantities and delivery status of an order line.
     *
     * This method updates the delivered quantity and remaining quantity of a specific order line.
     * It also updates the delivery status based on the remaining quantity.
     * If the remaining quantity is zero, the delivery status is set to 'Delivered' (3).
     * Otherwise, it is set to 'Partially Delivered' (2).
     * After updating the order line, an OrderLineUpdated event is triggered.
     *
     * @param int $orderLineId The ID of the order line to update.
     * @param int $qty The quantity to add to the delivered quantity.
     * @return void
     */
    public function updateOrderLine($orderLineId, $qty)
    {
        $OrderLine = OrderLines::find($orderLineId);
        $OrderLine->delivered_qty += $qty;
        $OrderLine->delivered_remaining_qty -= $qty;

        // Mise à jour du statut de la ligne de commande
        if ($OrderLine->delivered_remaining_qty == 0) {
            $OrderLine->delivery_status = 3;
        } else {
            $OrderLine->delivery_status = 2;
        }

        $OrderLine->save();
        event(new OrderLineUpdated($OrderLine->id));
    }

    /**
     * Update the stock location product ID for a purchase receipt line.
     *
     * This method updates the 'stock_location_products_id' field of a specific purchase receipt line
     * with the provided stock location product ID.
     *
     * @param int $purchaseReceiptLineId The ID of the purchase receipt line to update.
     * @param int $stockLocationProductId The ID of the stock location product to set.
     * @return void
     */
    public function updatePurchaseReceiptLine($purchaseReceiptLineId, $stockLocationProductId)
    {
        PurchaseReceiptLines::where('id', $purchaseReceiptLineId)
            ->update(['stock_location_products_id' => $stockLocationProductId]);
    }

    /**
     * Generate a unique batch number for traceability.
     *
     * This method creates a unique batch number by combining a prefix,
     * a timestamp, and a random string. The batch number is used for stock traceability.
     *
     * @return string The generated batch number.
     */
    public function generateBatchNumber()
    {
        // Add a prefix to identify batches
        $prefix = 'BATCH';

        // Generate a timestamp with the year, month, and day (e.g., 20250218)
        $datePart = now()->format('Ymd');

        // Generate a unique identifier (using Str::random for 5 random characters)
        $uniquePart = strtoupper(Str::random(5));

        // Combine the different parts to form the batch number
        $batchNumber = $prefix . '-' . $datePart . '-' . $uniquePart;

        return $batchNumber;
    }
}
