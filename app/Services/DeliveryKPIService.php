<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
        $cacheKey = 'delivery_monthly_recap_' . now()->year;
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($year) {
            return DB::table('delivery_lines')
                        ->join('order_lines', 'delivery_lines.order_line_id', '=', 'order_lines.id')
                        ->selectRaw('
                            MONTH(delivery_lines.created_at) AS month,
                            SUM((order_lines.selling_price * delivery_lines.qty)-(order_lines.selling_price * delivery_lines.qty)*(order_lines.discount/100)) AS orderSum
                        ')
                        ->whereYear('delivery_lines.created_at', $year)
                        ->groupByRaw('MONTH(delivery_lines.created_at)')
                        ->get();
        });
    }

    public function getDeliveryMonthlyProgress($month ,$year)
    {
        $cacheKey = 'delivery_monthly_progress_' . now()->year;
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($month, $year) {
            return DB::table('delivery_lines')
                        ->join('order_lines', 'delivery_lines.order_line_id', '=', 'order_lines.id')
                        ->selectRaw('FLOOR(SUM((order_lines.selling_price * delivery_lines.qty)-(order_lines.selling_price * delivery_lines.qty)*(order_lines.discount/100))) AS orderSum')
                        ->whereYear('delivery_lines.created_at', '=', $year)
                        ->whereMonth('delivery_lines.created_at', $month)
                        ->first() ?? (object) ['orderSum' => 0]; 
        });
    }
}
