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

    /**
     * Get the monthly recap of deliveries for a given year.
     *
     * This function retrieves the monthly summary of deliveries for the specified year.
     * It calculates the total order sum for each month by joining the `delivery_lines`
     * and `order_lines` tables, and applying the necessary calculations for the order sum.
     * The result is cached for 1 hour to improve performance.
     *
     * @param int $year The year for which to retrieve the delivery monthly recap.
     * @return \Illuminate\Support\Collection The collection containing the monthly recap of deliveries.
     */
    public function getDeliveryMonthlyRecap($year, ?\Carbon\Carbon $start = null, ?\Carbon\Carbon $end = null)
    {
        $cacheKey = $start
            ? 'delivery_monthly_recap_fiscal_' . $start->format('Ymd')
            : 'delivery_monthly_recap_' . $year;

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($year, $start, $end) {
            $query = DB::table('delivery_lines')
                        ->join('order_lines', 'delivery_lines.order_line_id', '=', 'order_lines.id')
                        ->selectRaw('
                            MONTH(delivery_lines.created_at) AS month,
                            SUM((order_lines.selling_price * delivery_lines.qty)-(order_lines.selling_price * delivery_lines.qty)*(order_lines.discount/100)) AS orderSum
                        ');

            if ($start && $end) {
                $query->whereBetween('delivery_lines.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
            } else {
                $query->whereYear('delivery_lines.created_at', $year);
            }

            return $query->groupByRaw('MONTH(delivery_lines.created_at)')->get();
        });
    }

    /**
     * Get the monthly delivery progress.
     *
     * This method retrieves the total sum of delivered orders for a given month and year.
     * The result is cached for one hour to improve performance.
     *
     * @param int $month The month for which to retrieve the delivery progress.
     * @param int $year The year for which to retrieve the delivery progress.
     * @return object An object containing the total sum of delivered orders (orderSum).
     */
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
