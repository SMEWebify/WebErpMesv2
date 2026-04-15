<?php

namespace App\Services\AI\Tools;

use App\Models\Products\Products;
use App\Models\Products\StockLocationProducts;

/**
 * Outil de consultation du stock par produit/matière.
 * Exposé à Claude via le tool use.
 */
class StockQueryTool
{
    /**
     * Vérifie le stock d'un produit ou d'une matière première.
     *
     * @param  string|null  $reference  Code produit (ex: REF-001)
     * @param  string|null  $label      Libellé ou partie du libellé
     * @param  int          $limit      Nombre max de produits retournés
     */
    public function check(
        ?string $reference = null,
        ?string $label     = null,
        int     $limit     = 10,
    ): array {
        if (empty($reference) && empty($label)) {
            return ['error' => 'Fournissez au moins une référence ou un libellé de produit.'];
        }

        $products = Products::with(['Stock_location_product.StockLocation'])
            ->select(['id', 'code', 'label', 'material', 'methods_units_id'])
            ->when($reference, fn ($q) => $q->where('code', 'like', "%{$reference}%"))
            ->when($label, function ($q) use ($label) {
                // Recherche mot par mot : "alu EP5" → chaque mot testé sur label, code, material
                $words = array_values(array_filter(preg_split('/\s+/', trim($label)), fn ($w) => mb_strlen($w) >= 2));
                foreach ($words as $word) {
                    $q->where(function ($sub) use ($word) {
                        $sub->where('label',    'like', "%{$word}%")
                            ->orWhere('code',     'like', "%{$word}%")
                            ->orWhere('material', 'like', "%{$word}%");
                    });
                }
            })
            ->limit($limit)
            ->get();

        if ($products->isEmpty()) {
            return ['found' => false, 'message' => 'Aucun produit trouvé dans le stock.'];
        }

        $results = $products->map(function (Products $product) {
            $stockLines = $product->Stock_location_product;

            $totalStock = 0;
            $locations  = [];

            foreach ($stockLines as $line) {
                $qty = $line->getCurrentStockMove();
                $totalStock += $qty;
                $locations[] = [
                    'emplacement' => $line->StockLocation?->label ?? 'Inconnu',
                    'quantite'    => round($qty, 3),
                ];
            }

            return [
                'reference'    => $product->code,
                'label'        => $product->label,
                'materiau'     => $product->material,
                'stock_total'  => round($totalStock, 3),
                'emplacements' => $locations,
                'disponible'   => $totalStock > 0,
            ];
        });

        return [
            'found'    => true,
            'count'    => $results->count(),
            'products' => $results->toArray(),
        ];
    }

    /** Définition JSON Schema pour Claude */
    public static function definition(): array
    {
        return [
            'name'        => 'check_stock',
            'description' => 'Vérifie la disponibilité en stock d\'un produit ou d\'une matière première. Retourne la quantité disponible par emplacement.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'reference' => [
                        'type'        => 'string',
                        'description' => 'Référence/code du produit. Ex: "REF-001", "TOLE-304".',
                    ],
                    'label' => [
                        'type'        => 'string',
                        'description' => 'Libellé ou partie du libellé du produit. Ex: "acier inox", "tôle 2mm".',
                    ],
                    'limit' => [
                        'type'        => 'integer',
                        'description' => 'Nombre max de produits retournés (défaut: 10).',
                        'default'     => 10,
                    ],
                ],
                'required' => [],
            ],
        ];
    }
}
