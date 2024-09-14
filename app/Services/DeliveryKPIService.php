<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DeliveryKPIService
{
    /**
     * Retrieves the rate of grouped delivery by status.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDeliveriesDataRate()
    {
        return DB::table('deliverys')
                ->select('statu', DB::raw('count(*) as DeliveryCountRate'))
                ->groupBy('statu')
                ->get();
    }

    public function getDeliveryMonthlyRecap($year)
    {
        return DB::table('delivery_lines')
                    ->join('order_lines', 'delivery_lines.order_line_id', '=', 'order_lines.id')
                    ->selectRaw('
                        MONTH(delivery_lines.created_at) AS month,
                        SUM((order_lines.selling_price * delivery_lines.qty)-(order_lines.selling_price * delivery_lines.qty)*(order_lines.discount/100)) AS orderSum
                    ')
                    ->whereYear('delivery_lines.created_at', $year)
                    ->groupByRaw('MONTH(delivery_lines.created_at)')
                    ->get();
    }

    public function getDeliveryMonthlyProgress($month ,$year)
    {
        return DB::table('delivery_lines')
                    ->join('order_lines', 'delivery_lines.order_line_id', '=', 'order_lines.id')
                    ->selectRaw('FLOOR(SUM((order_lines.selling_price * delivery_lines.qty)-(order_lines.selling_price * delivery_lines.qty)*(order_lines.discount/100))) AS orderSum')
                    ->whereYear('delivery_lines.created_at', '=', $year)
                    ->whereMonth('delivery_lines.created_at', $month)
                    ->first() ?? (object) ['orderSum' => 0]; 
    }
}
