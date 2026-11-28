<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Products\Products;
use App\Models\Products\StockLocationProducts;
use App\Services\Stock\SheetRemnants;
use Illuminate\Http\Request;

/**
 * Chutes de tôle en stock, pour une FAO tierce qui veut imbriquer dessus : format,
 * matière, épaisseur, lot / coulée, quantité, valeur. La découpe se déclare ensuite
 * par `POST /api/nests` (format et lot de la chute dans `sheet`). Lecture seule.
 *
 * Jeton Sanctum ; un jeton restreint doit porter la capacité « remnants:read ».
 * Même droit que l'écran du stock (`stock-lot-serial-management`).
 */
class RemnantController extends Controller
{
    /** Articles examinés au plus par appel (filtrer par matière / épaisseur au-delà). */
    private const MAX_PRODUCTS = 500;

    public function index(Request $request, SheetRemnants $remnants)
    {
        abort_unless($request->user()?->tokenCan('remnants:read'), 403);
        abort_unless($request->user()->can('stock-lot-serial-management'), 403);

        $filters = $request->validate([
            'product_code'   => 'nullable|string|max:255',
            'material'       => 'nullable|string|max:255',
            'thickness'      => 'nullable|numeric|gt:0',
            'min_x'          => 'nullable|numeric|gt:0',
            'min_y'          => 'nullable|numeric|gt:0',
            'include_sheets' => 'nullable|boolean',
        ]);

        $products = Products::query()
            ->whereIn('id', StockLocationProducts::query()->select('products_id'))
            ->when(isset($filters['product_code']), fn ($q) => $q->where('code', $filters['product_code']))
            ->when(isset($filters['material']), fn ($q) => $q->where('material', $filters['material']))
            ->when(isset($filters['thickness']), fn ($q) => $q->whereBetween('thickness', [(float) $filters['thickness'] - 0.001, (float) $filters['thickness'] + 0.001]))
            ->orderBy('id')
            ->limit(self::MAX_PRODUCTS)
            ->get(['id', 'code', 'label', 'material', 'thickness', 'x_size', 'y_size'])
            ->keyBy('id');

        $pieces = collect($remnants->available($products->keys()->all(), (bool) ($filters['include_sheets'] ?? false)));

        $minX = (float) ($filters['min_x'] ?? 0);
        $minY = (float) ($filters['min_y'] ?? 0);
        if ($minX > 0 || $minY > 0) {
            // Rotation admise : la chute contient un rectangle min_x × min_y dans un sens ou l'autre.
            $pieces = $pieces->filter(function ($p) use ($minX, $minY) {
                $x = (float) $p['x_size'];
                $y = (float) $p['y_size'];

                return ($x >= $minX && $y >= $minY) || ($x >= $minY && $y >= $minX);
            });
        }

        $lines = StockLocationProducts::with('StockLocation:id,code,label')
            ->whereIn('id', $pieces->pluck('slp_id')->unique())
            ->get(['id', 'code', 'stock_locations_id', 'unit_cost'])
            ->keyBy('id');

        $data = $pieces
            ->sortBy([
                fn ($a, $b) => $a['products_id'] <=> $b['products_id'],
                fn ($a, $b) => ((float) $b['x_size'] * (float) $b['y_size']) <=> ((float) $a['x_size'] * (float) $a['y_size']),
            ])
            ->map(function ($p) use ($products, $lines) {
                $product = $products[$p['products_id']];
                $line = $lines[$p['slp_id']] ?? null;

                return [
                    'product'     => [
                        'id'        => $product->id,
                        'code'      => $product->code,
                        'label'     => $product->label,
                        'material'  => $product->material,
                        'thickness' => $product->thickness !== null ? (float) $product->thickness : null,
                        'sheet_x'   => $product->x_size !== null ? (float) $product->x_size : null,
                        'sheet_y'   => $product->y_size !== null ? (float) $product->y_size : null,
                    ],
                    'is_remnant'  => (bool) $p['is_remnant'],
                    'x_size'      => $p['x_size'],
                    'y_size'      => $p['y_size'],
                    'z_size'      => $p['z_size'] ?? ($product->thickness !== null ? (float) $product->thickness : null),
                    'qty'         => round((float) $p['qty'], 3),
                    'batch'       => $p['batch_code'],
                    'heat_number' => $p['heat_number'],
                    'stock_line'  => [
                        'id'       => $p['slp_id'],
                        'code'     => $line?->code,
                        'location' => $line?->StockLocation?->code ?? $line?->StockLocation?->label,
                    ],
                    'unit_value'  => self::unitValue($p, $product, $line),
                    // Contour réel de la chute (DXF / SVG), contenu par GET /api/files/json/{id}/download.
                    'drawings'    => array_map(fn ($d) => [
                        'id'   => $d['id'],
                        'name' => $d['name'],
                    ], $p['drawings'] ?? []),
                ];
            })
            ->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'count'     => $data->count(),
                'truncated' => $products->count() >= self::MAX_PRODUCTS,
            ],
        ]);
    }

    /** Valeur d'une pièce : coût moyen de la ligne × surface pièce / surface tôle ; inconnue → null. */
    private static function unitValue(array $piece, Products $product, ?StockLocationProducts $line): ?float
    {
        $cost = (float) ($line?->unit_cost ?? 0);
        $sheet = (float) $product->x_size * (float) $product->y_size;
        $area = (float) $piece['x_size'] * (float) $piece['y_size'];

        if (! $piece['is_remnant']) {
            return $cost > 0 ? round($cost, 2) : null;
        }

        return $cost > 0 && $sheet > 0 && $area > 0 ? round($cost * $area / $sheet, 2) : null;
    }
}
