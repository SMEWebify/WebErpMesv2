<?php
namespace App\Services;

use App\Models\Workflow\OrderLines;
use App\Models\Products\StockMove;
use Illuminate\Support\Facades\DB;

class ABC_MFR_CalculatorService
{
    /**
     * Calculate ABC and FMR analysis for a given product.
     *
     * This function performs the following steps:
     * 1. Collects sales data for the specified product.
     * 2. Calculates the ABC analysis based on the total value of sales.
     * 3. Categorizes products into A, B, and C categories based on their total sales value.
     * 4. Calculates the FMR analysis based on product usage frequency and movement.
     * 5. Integrates the ABC and FMR data to determine the final category for each product.
     *
     * @param int|null $productId The ID of the product to analyze. If null, all products are analyzed.
     * @return \Illuminate\Support\Collection A collection of products with their ABC and FMR categories.
     */
    public function calculateABC_FMR($productId = null)
    {
        
        // Collecte des Données
        $salesData = OrderLines::select(
            'product_id',
            DB::raw('SUM(qty) as total_quantity'),
            DB::raw('SUM(selling_price * qty) as total_value')
        )
        ->groupBy('product_id')
        ->where('product_id', $productId)
        ->get();

        // Calcul de l'Analyse ABC
        $totalValue = $salesData->sum('total_value');
        $aThreshold = $totalValue * 0.8;
        $bThreshold = $totalValue * 0.15;

        $sortedProducts = $salesData->sortByDesc('total_value');

        $categoryA = [];
        $categoryB = [];
        $categoryC = [];

        $currentValue = 0;

        foreach ($sortedProducts as $product) {
            $currentValue += $product->total_value;
            if ($currentValue <= $aThreshold) {
                $categoryA[] = $product->product_id;
            } elseif ($currentValue <= $aThreshold + $bThreshold) {
                $categoryB[] = $product->product_id;
            } else {
                $categoryC[] = $product->product_id;
            }
        }

        // Calcul de l'Analyse FMR
        $productUsageFrequency = OrderLines::select(
            'product_id',
            DB::raw('COUNT(DISTINCT orders_id) as usage_frequency')
        )
        ->where('product_id', $productId)
        ->groupBy('product_id')
        ->get();

        $productMovement = OrderLines::select(
            'product_id',
            DB::raw('COUNT(*) as movement')
        )
        ->where('product_id', $productId)
        ->groupBy('product_id')
        ->get();

        // Intégration des Données ABC/FMR
        return $salesData->map(function ($item) use ($categoryA, $categoryB, $categoryC, $productUsageFrequency, $productMovement) {
            $productId = $item->product_id;

            $abcCategory = in_array($productId, $categoryA) ? 'A' : (in_array($productId, $categoryB) ? 'B' : 'C');
            $frequency = $productUsageFrequency->where('product_id', $productId)->first()->usage_frequency ?? 0;
            $movement = $productMovement->where('product_id', $productId)->first()->movement ?? 0;

            $fmrCategory = '';
            if ($frequency > 80) {  
                $fmrCategory = 'F';
            } elseif ($movement > 15) {
                $fmrCategory = 'M';
            } else {
                $fmrCategory = 'R';
            }

            return [
                'product_id' => $productId,
                'abc_category' => $abcCategory,
                'fmr_category' => $fmrCategory,
                'category' => $abcCategory . $fmrCategory
            ];
        });
    }
}
