<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Products\StockLocationProducts;

class StockCalculationService
{
    // Mouvements entrants (augmentent le stock)
    private const INCOMING = [1, 3, 5, 12, 14];

    protected $stockLocationProduct;

    public function __construct(StockLocationProducts $stockLocationProduct)
    {
        $this->stockLocationProduct = $stockLocationProduct;
    }

    /**
     * Calcule le CUMP historique complet via SQL pour un emplacement donné.
     * Utilisé pour l'initialisation ou la vérification.
     */
    public function calculateWeightedAverageCost(int $stockLocationProductId): float
    {
        $row = DB::table('stock_moves')
            ->where('stock_location_products_id', $stockLocationProductId)
            ->whereIn('typ_move', self::INCOMING)
            ->selectRaw('
                COALESCE(SUM(qty), 0)                   as total_qty,
                COALESCE(SUM(qty * component_price), 0) as total_value
            ')
            ->first();

        if (!$row || $row->total_qty <= 0) {
            return 0.0;
        }

        return (float) ($row->total_value / $row->total_qty);
    }

    /**
     * Recalcule le CUMP incrémentalement après un mouvement entrant et le persiste.
     * Formule : (qty_actuelle × coût_actuel + qty_entrante × prix) / (qty_actuelle + qty_entrante)
     */
    public function recalculateAndPersist(int $stockLocationProductId, float $incomingQty, float $incomingPrice): void
    {
        $slp = StockLocationProducts::lockForUpdate()->find($stockLocationProductId);
        if (!$slp) return;

        $currentQty  = (float) $slp->getCurrentStockMove();
        $currentCost = (float) $slp->unit_cost;
        $newTotalQty = $currentQty + $incomingQty;

        if ($newTotalQty <= 0) return;

        $newUnitCost = (($currentQty * $currentCost) + ($incomingQty * $incomingPrice)) / $newTotalQty;

        $slp->unit_cost = round($newUnitCost, 4);
        $slp->save();
    }

    public function canDispatch($tracabilityId, $quantityRequested, $stockLocationProductId): bool
    {
        $slp = StockLocationProducts::find($stockLocationProductId);

        return $slp && $slp->getCurrentStockMove($tracabilityId) >= $quantityRequested;
    }
}
