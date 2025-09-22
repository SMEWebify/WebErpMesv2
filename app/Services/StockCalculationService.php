<?php

namespace App\Services;

use App\Models\Products\StockMove;
use App\Models\Products\StockLocationProducts;

class StockCalculationService
{
    protected $stockLocationProduct;

    public function __construct(StockLocationProducts $stockLocationProduct)
    {
        $this->stockLocationProduct = $stockLocationProduct;
    }

    /**
     * Calculate the weighted average cost of a product in a stock location.
     *
     * This function calculates the weighted average cost of a product based on its stock movements
     * in a specific stock location. It considers only the input movements (types 1, 3, 5, and 12)
     * to compute the total quantity and total value, and then calculates the average cost.
     *
     * @param int $stockLocationProductId The ID of the stock location product.
     * @return float The weighted average cost of the product. Returns 0 if there is no quantity.
     */
    public function calculateWeightedAverageCost(int $stockLocationProductId): float
    {
        $stockLocationProduct = StockLocationProducts::find($stockLocationProductId);

        if (! $stockLocationProduct) {
            return 0.0;
        }

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

        return 0.0; // If no quantity, return zero or other default behavior
    }

    public function canDispatch($tracabilityId, $quantityRequested, $stockLocationProductId)
    {
        // Récupérer l'objet StockLocationProducts correspondant
        $stockLocationProduct = StockLocationProducts::find($stockLocationProductId);
    
        // Utiliser la méthode getCurrentStockMove avec la traçabilité pour obtenir le stock disponible
        $stockAvailable = $stockLocationProduct->getCurrentStockMove($tracabilityId);
    
        // Vérifier si la quantité demandée est disponible
        if ($stockAvailable >= $quantityRequested) {
            return true;
        }
    
        return false;
    }
    
}
