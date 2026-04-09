<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Services\QuoteKPIService;
use App\Services\OrderKPIService;
use App\Services\DeliveryKPIService;
use App\Services\InvoiceKPIService;
use App\Services\PurchaseKPIService;
use App\Services\OrderLinesService;
use App\Models\Admin\EstimatedBudgets;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class KpiController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'has.role', 'check.factory']);
    }

    /**
     * GET /kpi/recent/orders?limit=5
     *
     * Returns the N most recent orders with company, status and formatted price.
     * Shape: [{ id, code, statu, type, companies_id, companie_label,
     *           formatted_total_price, validity_date }]
     */
    public function recentOrders(Request $request)
    {
        $limit = min($request->integer('limit', 5), 20);

        $orders = Orders::with('companie:id,label')
            ->orderByDesc('id')
            ->take($limit)
            ->get()
            ->map(fn ($o) => [
                'id'                   => $o->id,
                'code'                 => $o->code,
                'statu'                => $o->statu,
                'type'                 => $o->type,
                'companies_id'         => $o->companies_id,
                'companie_label'       => $o->companie?->label,
                'formatted_total_price'=> $o->formatted_total_price,
                'validity_date'        => $o->validity_date,
            ]);

        return response()->json($orders);
    }

    /**
     * GET /kpi/recent/quotes?limit=5
     *
     * Returns the N most recent quotes with company, status and formatted price.
     * Shape: [{ id, code, statu, companies_id, companie_label,
     *           formatted_total_price, created_at_human }]
     */
    public function recentQuotes(Request $request)
    {
        $limit = min($request->integer('limit', 5), 20);

        $quotes = Quotes::with('companie:id,label')
            ->orderByDesc('id')
            ->take($limit)
            ->get()
            ->map(fn ($q) => [
                'id'                   => $q->id,
                'code'                 => $q->code,
                'statu'                => $q->statu,
                'companies_id'         => $q->companies_id,
                'companie_label'       => $q->companie?->label,
                'formatted_total_price'=> $q->formatted_total_price,
                'created_at_human'     => $q->GetPrettyCreatedAttribute(),
            ]);

        return response()->json($quotes);
    }

    /**
     * GET /kpi/delivery/board
     *
     * Returns incoming orders (≤ 2 days) and late orders with their overflow counts.
     * Shape:
     * {
     *   incoming: [{ orders_id, delivery_date, order: { id, code } }],
     *   incoming_more: int,   -- orders beyond the 10 shown (actually count - 4 per service)
     *   late:     [{ orders_id, delivery_date, order: { id, code } }],
     *   late_more: int,
     * }
     */
    public function deliveryBoard(OrderLinesService $service)
    {
        $incoming = $service->getIncomingOrders(10);
        $late     = $service->getLateOrders(10);

        $serialize = fn ($lines) => $lines->map(fn ($line) => [
            'orders_id'     => $line->orders_id,
            'delivery_date' => $line->delivery_date,
            'order'         => $line->order ? [
                'id'   => $line->order->id,
                'code' => $line->order->code,
            ] : null,
        ])->values();

        return response()->json([
            'incoming'      => $serialize($incoming),
            'incoming_more' => $service->getIncomingOrdersCount(),
            'late'          => $serialize($late),
            'late_more'     => $service->getLateOrdersCount(),
        ]);
    }

    /**
     * GET /kpi/quotes/rate?year=2026&company_id=42
     *
     * Returns quote counts grouped by status for a given year.
     * Shape: { year, data: [{ statu, QuoteCountRate }] }
     */
    public function quoteRate(Request $request, QuoteKPIService $service)
    {
        $year      = $request->integer('year', Carbon::now()->year);
        $companyId = $request->integer('company_id') ?: null;

        return response()->json([
            'year' => $year,
            'data' => $service->getQuotesDataRate($year, $companyId),
        ]);
    }

    /**
     * GET /kpi/orders/monthly?year=2026
     *
     * Returns monthly breakdown of orders, deliveries, invoices, purchases and target.
     * All series: [{ month: int, orderSum|purchaseSum: float }]
     * Target: { amount1..amount12 } (null if not configured)
     * Purchases: null if user lacks purchases-menu permission.
     *
     * Shape:
     * {
     *   year,
     *   orders:     [{ month, orderSum }],
     *   deliveries: [{ month, orderSum }],
     *   invoices:   [{ month, orderSum }],
     *   purchases:  [{ month, purchaseSum }] | null,
     *   target:     { amount1..amount12 }   | null,
     * }
     */
    public function ordersMonthly(
        Request         $request,
        OrderKPIService   $orders,
        DeliveryKPIService $deliveries,
        InvoiceKPIService  $invoices,
        PurchaseKPIService $purchases,
    ) {
        $year = $request->integer('year', Carbon::now()->year);

        $target = EstimatedBudgets::where('year', $year)->first();

        return response()->json([
            'year'       => $year,
            'orders'     => $orders->getOrderMonthlyRecap($year),
            'deliveries' => $deliveries->getDeliveryMonthlyRecap($year),
            'invoices'   => $invoices->getInvoiceMonthlyRecap($year),
            'purchases'  => Gate::allows('purchases-menu')
                                ? $purchases->getPurchaseMonthlyRecap($year)
                                : null,
            'target'     => $target,
        ]);
    }
}
