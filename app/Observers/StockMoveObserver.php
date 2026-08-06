<?php

namespace App\Observers;

use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Services\StockReservationService;

class StockMoveObserver
{
    public function __construct(private readonly StockReservationService $reservations) {}

    public function created(StockMove $move): void
    {
        if (!$move->stock_location_products_id) {
            return;
        }

        // Le mouvement porte sur un emplacement produit précis : on remonte au
        // products_id pour recalculer la répartition sur toutes les tâches
        // qui consomment ce composant.
        $productId = StockLocationProducts::whereKey($move->stock_location_products_id)->value('products_id');

        if ($productId) {
            $this->reservations->recomputeForProduct((int) $productId);
        }
    }
}
