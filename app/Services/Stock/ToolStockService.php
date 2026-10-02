<?php

namespace App\Services\Stock;

use App\Models\Methods\MethodsTools;
use App\Models\Products\Products;
use App\Models\Products\StockLocationProducts;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Stock des outils : un outil ne porte pas de stock lui-même, il pointe vers un article
 * acheté (methods_tools.products_id) qui profite de toute la chaîne existante —
 * emplacements, seuil mini, réceptions, CUMP, prix fournisseurs, réappro.
 */
class ToolStockService
{
    /**
     * Crée l'article de stock de l'outil et, si un emplacement est fourni, sa ligne de stock.
     *
     * @param array{methods_services_id:int, methods_families_id:int, methods_units_id:int,
     *              stock_locations_id?:int|null, mini_qty?:float|null, purchased_price?:float|null,
     *              qty_eco_min?:float|null} $data
     */
    public function createProductForTool(MethodsTools $tool, array $data): Products
    {
        return DB::transaction(function () use ($tool, $data) {
            $product = Products::create([
                'code'                => $tool->code,
                'label'               => $tool->label ?: $tool->code,
                'methods_services_id' => $data['methods_services_id'],
                'methods_families_id' => $data['methods_families_id'],
                'methods_units_id'    => $data['methods_units_id'],
                'purchased'           => 1,
                'sold'                => 2,
                'tracability_type'    => 1,
                'purchased_price'     => $data['purchased_price'] ?? $tool->cost,
                'qty_eco_min'         => $data['qty_eco_min'] ?? null,
                'comment'             => $tool->comment,
            ]);

            if (!empty($data['stock_locations_id'])) {
                StockLocationProducts::create([
                    'code'               => $product->code,
                    'user_id'            => Auth::id(),
                    'stock_locations_id' => $data['stock_locations_id'],
                    'products_id'        => $product->id,
                    'mini_qty'           => $data['mini_qty'] ?? 0,
                ]);
            }

            $tool->update(['products_id' => $product->id]);

            return $product;
        });
    }

    /**
     * Stock d'un lot d'outils, en une passe : [tool_id => ['stock' => x, 'mini' => y, 'below' => bool]].
     *
     * @param \Illuminate\Support\Collection<int, MethodsTools> $tools outils avec stockProduct.Stock_location_product chargé
     */
    public function stockFor($tools): array
    {
        $out = [];
        foreach ($tools as $tool) {
            $product = $tool->stockProduct;
            if (!$product) {
                continue;
            }
            $stock = (float) $product->getTotalStockMove();
            $mini  = (float) $product->Stock_location_product->sum('mini_qty');
            $out[$tool->id] = ['stock' => $stock, 'mini' => $mini, 'below' => $mini > 0 && $stock < $mini];
        }

        return $out;
    }
}
