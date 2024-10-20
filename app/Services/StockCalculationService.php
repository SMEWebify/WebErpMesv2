<?php

namespace App\Services;

use App\Models\Products\StockLocationProducts;

class StockCalculationService
{
    protected $stockLocationProduct;

    public function __construct(StockLocationProducts $stockLocationProduct)
    {
        $this->stockLocationProduct = $stockLocationProduct;
    }

    /**
     * Calcul du prix pondéré moyen d'un produit dans un emplacement de stock.
     */
    public function calculateWeightedAverageCost($stockLocationProductId)
    {
        $stockLocationProduct = StockLocationProducts::find($stockLocationProductId);

        // Récupérer tous les mouvements de stock pour ce produit dans l'emplacement
        $stockMoves = $stockLocationProduct->StockMove;

        $totalQuantity = 0;
        $totalValue = 0;

        foreach ($stockMoves as $move) {
            // Only consider inputs (according to 'typ_move')
            if (in_array($move->typ_move, [1, 3, 5, 12])) {
                $totalQuantity += $move->qty;
                $totalValue += $move->qty * $move->component_price;
            }
        }

        // Calculate the average weighted cost
        if ($totalQuantity > 0) {
            return $totalValue / $totalQuantity;
        }

        return 0; // If no quantity, return zero or other default behavior
    }
}
