<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Deliverys;
use Illuminate\Support\Facades\DB;
use App\Models\Workflow\OrderLines;
use Illuminate\Support\Facades\Cache;

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
        $cacheKey = 'delivered_orders_percentage_' . now()->year;
        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $totalOrders = OrderLines::whereYear('created_at', now()->year)->count();
            
            if ($totalOrders === 0) {
                return 0;
            }

            $deliveredOrders = OrderLines::whereYear('created_at', now()->year)
                                            ->where('delivery_status', '=', 3)
                                            ->count();

            return round(($deliveredOrders / $totalOrders) * 100,2);
        });
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
        $cacheKey = 'invoiced_orders_percentage_' . now()->year;
        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $totalOrders = OrderLines::whereYear('created_at', now()->year)->count();
            
            if ($totalOrders === 0) {
                return 0;
            }

            $invoicedOrders = OrderLines::whereYear('created_at', now()->year)
                                    ->where('invoice_status', 3)
                                    ->count();

            return round(($invoicedOrders / $totalOrders) * 100,2);
        });
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
        $cacheKey = 'late_orders_count_' . now()->year;
        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return Orders::whereYear('created_at', now()->year)
                ->whereHas('orderLines', function($query) {
                    $query->where('delivery_status', 1) // delivered quantity < ordered quantity
                        ->where('delivery_date', '<', now());     // expected delivery date has passed 
                })->count();
        });
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
     * Retrieves the monthly summary of orders for the current year, filtered by company.
     *
     * @param int $year
     * @param int|null $companyId
     * @return \Illuminate\Support\Collection
     */
    public function getOrderMonthlyRecap($year, $companyId = null)
    {
        $cacheKey = 'order_monthly_recap_' . $year . '_company_' . ($companyId ?? 'all');
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($year, $companyId) {
            // Commence la requête avec une jointure et un filtrage éventuel par compagnie
            $query = DB::table('order_lines')
                ->selectRaw('
                    MONTH(delivery_date) AS month,
                    SUM((selling_price * qty)-(selling_price * qty)*(discount/100)) AS orderSum
                ')
                ->leftJoin('orders', function ($join) {
                    $join->on('order_lines.orders_id', '=', 'orders.id')
                        ->where('orders.type', '=', 1); // Filtre par le type de commande
                })
                ->whereYear('order_lines.created_at', $year)
                ->groupByRaw('MONTH(order_lines.delivery_date)');

            // If a company ID is provided, add the filter
            if ($companyId) {
                $query->where('orders.companies_id', $companyId);
            }

            // Execute and return results
            return $query->get();
        });
    }

        /**
     * Retrieves the monthly summary of order for the last year.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderMonthlyRecapPreviousYear($year)
    {
        $lastyear = $year-1;
        $cacheKey = 'order_monthly_recap_lastyear_' . $lastyear;
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($lastyear) {
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
        });
    }

    /**
     * Retrieves the monthly summary of order for the current month.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderMonthlyRemainingToDelivery($month ,$year)
    {
        $cacheKey = 'order_remaining_delivery_' . $year;
        return Cache::remember($cacheKey, now()->addMinutes(10), function ()use ($year, $month) {
            return DB::table('order_lines')
                        ->selectRaw('
                            FLOOR(SUM((selling_price * delivered_remaining_qty)-(selling_price * delivered_remaining_qty)*(discount/100))) AS orderSum
                        ')
                        ->whereYear('delivery_date', '=', $year)
                        ->whereMonth('delivery_date', $month)
                        ->groupByRaw('MONTH(delivery_date) ')
                        ->first() ?? (object) ['orderSum' => 0]; 
        });
    }

        /**
     * Retrieves the monthly summary of order for the current month.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderMonthlyRemainingToInvoice($companyId = null)
    {
        $cacheKey = 'order_remaining_invoice_' . now()->year . '_company_' . ($companyId ?? 'all');
        
        return Cache::remember($cacheKey, now()->addMinutes(1), function () use ($companyId) {
            $query = DB::table('order_lines')
                        ->selectRaw('
                            FLOOR(SUM((selling_price * invoiced_remaining_qty)-(selling_price * invoiced_remaining_qty)*(discount/100))) AS orderSum
                        ')
                        ->join('orders', 'order_lines.orders_id', '=', 'orders.id');

                        $query->where(function ($subQuery) {
                            $subQuery->where('order_lines.invoice_status', 1)
                                        ->orWhere('order_lines.invoice_status', 2);
                            });

                        if ($companyId) {
                            $query->where('orders.companies_id', $companyId);
                        }
                        
            return  $query->first() ?? (object) ['orderSum' => 0];
        });
    }

    /**
     * Retrieves the total amount summary of order for the comming current year.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderTotalForCast($year)
    {
        $cacheKey = 'order_total_forcast_' . now()->year;
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($year) {
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
        });
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
        $cacheKey = 'average_order_processing_time_' . now()->year;
        return Cache::remember($cacheKey, now()->addHours(1), function () {
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
        });
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

    /**
    * Calculate the Service Rate.
    *
    * The Service Rate is calculated by dividing the number of requests (order lines)
    * fulfilled on time (those with a delivery date less than or equal to the
    * expected date) by the total number of requests, then multiplying the result by 100
    *
    * @return float Service Rate as a percentage
    */
    public function getServiceRate()
    {
        $cacheKey = 'service_rate_' . now()->year;
        return Cache::remember($cacheKey, now()->addHours(1), function () {
            $totalOrderLines = OrderLines::where('delivery_status', 3)->count();

            $onTimeDeliveries = OrderLines::where('delivery_status', 3)
                                            ->whereHas('DeliveryLines', function ($query) {
                                                $query->whereColumn('delivery_lines.created_at', '<=', 'order_lines.delivery_date');
                                            })->count();

            if ($totalOrderLines === 0) {
                return 0; // Éviter la division par zéro
            }

            $serviceRate = round(($onTimeDeliveries / $totalOrderLines) * 100,2);

            return $serviceRate;
        });
    }

    /**
     * Calculates the average order price for a specific company or for all orders if no company ID is provided.
     * 
     * This function retrieves the valid orders either for a given company (if $companyId is provided) or for all orders
     * if no company is specified. It then calculates the total price for each order using the `OrderCalculatorService`
     * and returns the average order price.
     *
     * @param int|null $companyId The ID of the company for which to calculate the average order price. If null, the function calculates the average for all orders.
     * @return float The average price of the orders for the given company or all orders. Returns 0 if no orders are found.
     */
    public function getAverageOrderPriceAttribute($companyId = null)
    {
        // If a company ID is provided, filter orders by company
        $ordersQuery = Orders::where('statu', '!=', 5);

        if ($companyId) {
            $ordersQuery->where('companies_id', $companyId);
        }

        $orders = $ordersQuery->get();

        // If no command is found, return 0
        if ($orders->count() === 0) {
            return 0;
        }

        // Use the service to calculate the total for each order
        $totalPrice = $orders->sum(function ($order) {
            $OrderCalculatorService = new OrderCalculatorService($order);
            return $OrderCalculatorService->getTotalPrice();
        });

        // Calculate and return the average
        return $totalPrice / $orders->count();
    }


}
