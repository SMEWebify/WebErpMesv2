<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Events\OrderLineUpdated;
use App\Models\Products\StockMove;
use App\Services\StockCalculationService;
use App\Models\Products\StockLocation;
use App\Models\Products\StockLocationProducts;
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
    // Mouvements entrants : recalcul CUMP déclenché après création
    private const INCOMING_TYPES = [1, 3, 5, 12, 14];

    public function createStockMove(array $data): StockMove
    {
        $move = StockMove::create($data);

        if (
            in_array($data['typ_move'] ?? null, self::INCOMING_TYPES, true)
            && isset($data['stock_location_products_id'])
            && ($data['qty'] ?? 0) > 0
        ) {
            app(StockCalculationService::class)->recalculateAndPersist(
                (int) $data['stock_location_products_id'],
                (float) $data['qty'],
                (float) ($data['component_price'] ?? 0),
            );
        }

        return $move;
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
        event(new OrderLineUpdated($OrderLine));
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
     * Transfer stock from one location to another.
     *
     * Creates two atomic stock moves (typ_move=4) inside a transaction:
     * an outgoing move on the source and an incoming move on the destination.
     * The destination StockLocationProducts is created automatically if it
     * does not yet exist. The tracability (batch number) is preserved.
     *
     * @param int $sourceId The source StockLocationProducts ID.
     * @param int $destinationLocationId The destination StockLocation ID.
     * @param float $qty Quantity to transfer.
     * @param string|null $tracability Batch/serial number to preserve.
     * @param int $userId The user performing the transfer.
     * @return StockLocationProducts The destination StockLocationProducts.
     *
     * @throws \Exception If not enough stock is available on the source.
     */
    public function transfer(int $sourceId, int $destinationLocationId, float $qty, ?string $tracability, int $userId): StockLocationProducts
    {
        return DB::transaction(function () use ($sourceId, $destinationLocationId, $qty, $tracability, $userId) {
            // Verrou pessimiste sur la ligne source : bloque toute autre
            // consommation/transfert concurrents sur cet emplacement.
            $source = StockLocationProducts::whereKey($sourceId)->lockForUpdate()->firstOrFail();

            // Disponible calculé en un SUM agrégé sous le verrou (évite TOCTOU
            // entre le check et l'écriture des mouvements).
            $availableQuery = StockMove::where('stock_location_products_id', $source->id)
                ->selectRaw("
                    COALESCE(SUM(CASE
                        WHEN typ_move IN (1,3,5,12,14) THEN qty
                        WHEN typ_move IN (2,4,6,9)     THEN -qty
                        ELSE 0
                    END), 0) AS available
                ");
            if ($tracability) {
                $availableQuery->where('tracability', $tracability);
            }
            $stockAvailable = (float) $availableQuery->value('available');

            if ($stockAvailable < $qty) {
                throw new \Exception(__('general_content.transfer_insufficient_stock_trans_key'));
            }

            // Outgoing move on source
            $this->createStockMove([
                'user_id'                    => $userId,
                'qty'                        => $qty,
                'stock_location_products_id' => $source->id,
                'typ_move'                   => 4,
                'tracability'                => $tracability,
            ]);

            // Find or create the product line on the destination location
            $destination = StockLocationProducts::firstOrCreate(
                [
                    'stock_locations_id' => $destinationLocationId,
                    'products_id'        => $source->products_id,
                ],
                [
                    'code'     => $this->generateTransferCode($destinationLocationId),
                    'user_id'  => $userId,
                    'mini_qty' => 0,
                ]
            );

            // Incoming move on destination (preserves tracability) — typ_move=14
            $this->createStockMove([
                'user_id'                    => $userId,
                'qty'                        => $qty,
                'stock_location_products_id' => $destination->id,
                'typ_move'                   => 14,
                'tracability'                => $tracability,
            ]);

            return $destination;
        });
    }

    /**
     * Generate a unique code for a new StockLocationProducts created during transfer.
     */
    private function generateTransferCode(int $stockLocationsId): string
    {
        $location = StockLocation::find($stockLocationsId);
        $base     = strtoupper(substr($location->code ?? 'LOC', 0, 8)) . '-' . strtoupper(Str::random(5));

        // Ensure uniqueness (extremely unlikely to collide, but safe)
        while (StockLocationProducts::where('code', $base)->exists()) {
            $base = strtoupper(substr($location->code ?? 'LOC', 0, 8)) . '-' . strtoupper(Str::random(5));
        }

        return $base;
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
