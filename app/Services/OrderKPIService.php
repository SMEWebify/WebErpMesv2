<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Workflow\OrderLines;
use Carbon\Carbon;

class OrderKPIService
{
    /**
     * Retrieves the monthly summary of order for the current year.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderMonthlyRecap($year)
    {
        return DB::table('order_lines')
                    ->selectRaw('
                        MONTH(delivery_date) AS month,
                        SUM((selling_price * qty)-(selling_price * qty)*(discount/100)) AS orderSum
                    ')
                    ->leftJoin('orders', function($join) {
                        $join->on('order_lines.orders_id', '=', 'orders.id')
                            ->where('orders.type', '=', 1);
                    })
                    ->whereYear('order_lines.created_at', $year)
                    ->groupByRaw('MONTH(order_lines.delivery_date)')
                    ->get();
    }

    /**
     * Retrieves the monthly summary of order for the current month.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderMonthlyRemaining($month ,$year)
    {
        return DB::table('order_lines')
                    ->selectRaw('
                        FLOOR(SUM((selling_price * qty)-(selling_price * qty)*(discount/100))) AS orderSum
                    ')
                    ->whereYear('delivery_date', '=', $year)
                    ->whereMonth('delivery_date', $month)
                    ->groupByRaw('MONTH(delivery_date) ')
                    ->get();
    }

    /**
     * Retrieves the total amount summary of order for the comming current year.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderTotalForCast($year)
    {
        return DB::table('order_lines')
                    ->selectRaw('
                        ROUND(SUM((selling_price * qty)-(selling_price * qty)*(discount/100)),2) AS orderTotalForCast
                    ')
                    ->leftJoin('orders', function($join) {
                        $join->on('order_lines.orders_id', '=', 'orders.id')
                            ->where('orders.type', '=', 1);
                    })
                    ->where('delivery_status', '=', 1)
                    ->orWhere('delivery_status', '=', 2)
                    ->whereYear('order_lines.delivery_date', $year)
                    ->get();
    }

    /**
     * Retrieves the rate of grouped orders by status.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrdersDataRate()
    {
        return DB::table('orders')
            ->select('statu', DB::raw('count(*) as OrderCountRate'))
            ->groupBy('statu')
            ->get();
    }
}
