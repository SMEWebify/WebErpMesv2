<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Deliverys;
use Illuminate\Support\Facades\DB;
use App\Models\Workflow\OrderLines;

class OrderKPIService
{

        /**
    * Calculates the percentage of orders fully delivered for the current year.
    *
    * An order is considered fully delivered if the delivered quantity (`delivered_qty`)
    * is greater than or equal to the ordered quantity (`qty`) for all associated order lines.
    *
    * @return float The percentage of orders delivered for the current year.
    */
    public function getDeliveredOrdersPercentage()
    {
        $totalOrders = OrderLines::whereYear('created_at', now()->year)->count();
        
        if ($totalOrders === 0) {
            return 0;
        }

        $deliveredOrders = OrderLines::whereYear('created_at', now()->year)
                                        ->where('delivery_status', '=', 3)
                                        ->count();

        return round(($deliveredOrders / $totalOrders) * 100,2);
    }

        /**
    * Calculates the percentage of orders that are fully invoiced for the current year.
    *
    * An order is considered fully invoiced if all of its order lines
    * have an `invoice_status` of 5, meaning that the line has been fully invoiced.
    *
    * @return float The percentage of orders that are invoiced for the current year.
    */
    public function getInvoicedOrdersPercentage()
    {
        $totalOrders = OrderLines::whereYear('created_at', now()->year)->count();
        
        if ($totalOrders === 0) {
            return 0;
        }

        $invoicedOrders = OrderLines::whereYear('created_at', now()->year)
                                ->where('invoice_status', 3)
                                ->count();

        return round(($invoicedOrders / $totalOrders) * 100,2);
    }

    /**
    * Get the number of backorders for the current year.
    *
    * An order is considered backordered if the expected delivery date
    * has passed and it has not yet been fully delivered (delivered quantity < ordered quantity).
    *
    * @return int The number of backorders for the current year.
    */
    public function getLateOrdersCount()
    {
        return Orders::whereYear('created_at', now()->year)
            ->whereHas('orderLines', function($query) {
                $query->where('delivery_status', 1) // delivered quantity < ordered quantity
                    ->where('delivery_date', '<', now());     // expected delivery date has passed 
            })->count();
    }

        /**
    * Retrieve all orders that have quantities still to be delivered for the current year.
    *
    * An order is considered to have a pending delivery if statu != 3
    *
     * @return int  of orders with pending deliveries for the current year.
    */
    public function getPendingDeliveries()
    {
        return Orders::where('statu', '!=', '3')->count();
    }


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
     * Retrieves the monthly summary of order for the last year.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderMonthlyRecapPreviousYear($year)
    {
        $lastyear = $year-1;
        return DB::table('order_lines')
                    ->selectRaw('
                        MONTH(delivery_date) AS month,
                        SUM((selling_price * qty)-(selling_price * qty)*(discount/100)) AS orderSum
                    ')
                    ->leftJoin('orders', function($join) {
                        $join->on('order_lines.orders_id', '=', 'orders.id')
                            ->where('orders.type', '=', 1);
                    })
                    ->whereYear('order_lines.created_at', $lastyear)
                    ->groupByRaw('MONTH(order_lines.delivery_date)')
                    ->get();
    }

    /**
     * Retrieves the monthly summary of order for the current month.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderMonthlyRemainingToDelivery($month ,$year)
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
     * Retrieves the monthly summary of order for the current month.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderMonthlyRemainingToInvoice()
    {
        return DB::table('order_lines')
                    ->selectRaw('
                        FLOOR(SUM((selling_price * qty)-(selling_price * qty)*(discount/100))) AS orderSum
                    ')
                    ->where('invoice_status', 1)
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

    /**
    * Calculate the order completion rate for the current year.
    *
    * An order is considered completed if all of its lines are fully delivered.
    *
    * @return float The order completion rate in percentage.
    */
    public function getOrderCompletionRate()
    {
        $totalOrders = Orders::whereYear('created_at', now()->year)->count();
        
        if ($totalOrders === 0) {
            return 0;
        }

        $completedOrders = Orders::whereYear('created_at', now()->year)
            ->where('statu', 3)
            ->count();

        return ($completedOrders / $totalOrders) * 100;
    }

    /**
    * Calculate the average processing time of an order for the current year.
    *
    * The processing time is the difference between the order creation date
    * and the date of the last associated delivery.
    *
    * @return float The average processing time in days.
    */
    public function getAverageOrderProcessingTime()
    {
        $orders = Orders::whereYear('created_at', now()->year)
            ->whereHas('orderLines', function($query) {
                $query->whereColumn('delivered_qty', '>=', 'qty');
            })->get();

        if ($orders->isEmpty()) {
            return 0;
        }

        $totalDays = $orders->map(function($order) {
            $lastDeliveryDate = Deliverys::where('order_id', $order->id)
                ->latest('created_at')
                ->value('created_at');
            
            return $lastDeliveryDate ? $lastDeliveryDate->diffInDays($order->created_at) : 0;
        })->sum();

        return $totalDays / $orders->count();
    }

    /**
    * Retrieve customers sorted by order volume for the current year.
    *
    * @param int $limit The number of customers to retrieve (default 5).
    * @return \Illuminate\Database\Eloquent\Collection Collection of customers sorted by order volume.
    */
    public function getTopCustomersByOrderVolume($limit = 5)
    {
        return Orders::select('companies_id', DB::raw('COUNT(*) as order_count'))
            ->whereYear('created_at', now()->year)
            ->groupBy('companies_id')
            ->orderBy('order_count', 'desc')
            ->take($limit)
            ->with('companie') // Assuming a relationship with companie model
            ->get();
    }

    /**
    * Get the number of pending orders for the current year.
    *
    * An order is pending if it is not fully delivered and the remaining quantity to be delivered is > 0.
    *
    * @return int The number of pending orders.
    */
    public function getPendingOrdersCount()
    {
        return Orders::whereYear('created_at', now()->year)->where('statu', '!=', 3)->count();
    }

}
