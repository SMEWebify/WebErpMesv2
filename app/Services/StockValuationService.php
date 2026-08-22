<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StockValuationService
{
    private const INCOMING = [1, 3, 5, 12, 14];
    // 15 = inventory shortage (regularisation move emitted by the counting workflow).
    private const OUTGOING = [2, 4, 6, 9, 15];

    /**
     * Total inventory value: SUM(current_qty × unit_cost) per stock_location_products.
     * Excludes lines where net qty ≤ 0 or unit_cost = 0.
     */
    public function getTotalInventoryValue(): float
    {
        $row = DB::table('stock_location_products as slp')
            ->join(
                DB::raw('(
                    SELECT stock_location_products_id,
                           COALESCE(SUM(CASE WHEN typ_move IN (' . implode(',', self::INCOMING) . ') THEN qty ELSE 0 END), 0)
                         - COALESCE(SUM(CASE WHEN typ_move IN (' . implode(',', self::OUTGOING) . ') THEN qty ELSE 0 END), 0)
                           AS net_qty
                    FROM stock_moves
                    GROUP BY stock_location_products_id
                ) AS sm'),
                'sm.stock_location_products_id',
                '=',
                'slp.id'
            )
            ->where('slp.unit_cost', '>', 0)
            ->where('sm.net_qty', '>', 0)
            ->selectRaw('COALESCE(SUM(sm.net_qty * slp.unit_cost), 0) AS total_value')
            ->first();

        return $row ? (float) $row->total_value : 0.0;
    }

    /**
     * Per-product valuation breakdown for reporting.
     * Returns rows: products_id, product_code, product_label, location_code, net_qty, unit_cost, total_value.
     */
    public function getValuationBreakdown(): array
    {
        return DB::table('stock_location_products as slp')
            ->join('products as p', 'p.id', '=', 'slp.products_id')
            ->join('stock_locations as sl', 'sl.id', '=', 'slp.stock_locations_id')
            ->join(
                DB::raw('(
                    SELECT stock_location_products_id,
                           COALESCE(SUM(CASE WHEN typ_move IN (' . implode(',', self::INCOMING) . ') THEN qty ELSE 0 END), 0)
                         - COALESCE(SUM(CASE WHEN typ_move IN (' . implode(',', self::OUTGOING) . ') THEN qty ELSE 0 END), 0)
                           AS net_qty
                    FROM stock_moves
                    GROUP BY stock_location_products_id
                ) AS sm'),
                'sm.stock_location_products_id',
                '=',
                'slp.id'
            )
            ->where('slp.unit_cost', '>', 0)
            ->where('sm.net_qty', '>', 0)
            ->select([
                'p.id   as products_id',
                'p.code as product_code',
                'p.label as product_label',
                'sl.code as location_code',
                'sm.net_qty',
                'slp.unit_cost',
                DB::raw('ROUND(sm.net_qty * slp.unit_cost, 2) as total_value'),
            ])
            ->orderBy('p.label')
            ->get()
            ->toArray();
    }
}
