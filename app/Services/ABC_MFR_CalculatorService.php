<?php
namespace App\Services;

use App\Models\Workflow\OrderLines;
use App\Models\Products\StockMove;
use Illuminate\Support\Facades\DB;

class ABC_MFR_CalculatorService
{
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
        ->groupBy('product_id')
        ->get();

        $productMovement = OrderLines::select(
            'product_id',
            DB::raw('COUNT(*) as movement')
        )
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
