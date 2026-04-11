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
use App\Services\QualityKPIService;
use App\Models\Workflow\OrderLines;
use App\Models\Admin\EstimatedBudgets;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Deliverys;
use App\Models\Purchases\Purchases;
use App\Models\Mood;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    /**
     * GET /kpi/recent/invoices?limit=8
     */
    public function recentInvoices(Request $request)
    {
        $limit = min($request->integer('limit', 8), 20);

        return response()->json(
            Invoices::with('companie:id,label')
                ->orderByDesc('id')
                ->take($limit)
                ->get()
                ->map(fn ($i) => [
                    'id'                    => $i->id,
                    'code'                  => $i->code,
                    'statu'                 => $i->statu,
                    'companies_id'          => $i->companies_id,
                    'companie_label'        => $i->companie?->label,
                    'formatted_total_price' => $i->formatted_total_price,
                    'created_at_human'      => $i->GetPrettyCreatedAttribute(),
                ])
        );
    }

    /**
     * GET /kpi/recent/deliveries?limit=8
     */
    public function recentDeliveries(Request $request)
    {
        $limit = min($request->integer('limit', 8), 20);

        return response()->json(
            Deliverys::with('companie:id,label')
                ->orderByDesc('id')
                ->take($limit)
                ->get()
                ->map(fn ($d) => [
                    'id'                    => $d->id,
                    'code'                  => $d->code,
                    'statu'                 => $d->statu,
                    'companies_id'          => $d->companies_id,
                    'companie_label'        => $d->companie?->label,
                    'formatted_total_price' => $d->formatted_total_price,
                    'created_at_human'      => $d->GetPrettyCreatedAttribute(),
                ])
        );
    }

    /**
     * GET /kpi/recent/purchases?limit=8
     * Retourne les commandes d'achat en attente de réception (statu 1, 2, 3)
     */
    public function recentPurchases(Request $request)
    {
        abort_unless(Gate::allows('purchases-menu'), 403);

        $limit = min($request->integer('limit', 8), 20);

        return response()->json(
            Purchases::with('companie:id,label')
                ->whereIn('statu', [1, 2, 3])
                ->orderByDesc('id')
                ->take($limit)
                ->get()
                ->map(fn ($p) => [
                    'id'                    => $p->id,
                    'code'                  => $p->code,
                    'statu'                 => $p->statu,
                    'companies_id'          => $p->companies_id,
                    'companie_label'        => $p->companie?->label,
                    'formatted_total_price' => $p->formatted_total_price,
                    'created_at_human'      => $p->GetPrettyCreatedAttribute(),
                ])
        );
    }

    /**
     * GET /kpi/top-clients
     * Top 5 clients par CA facturé (toutes années confondues)
     */
    public function topClients(InvoiceKPIService $service)
    {
        $clients = $service->getTopClients()->load('companie:id,label');

        return response()->json(
            $clients->map(fn ($c) => [
                'companies_id' => $c->companies_id,
                'total_amount' => (float) $c->total_amount,
                'companie'     => $c->companie ? ['label' => $c->companie->label] : null,
            ])
        );
    }

    /**
     * GET /kpi/nc-stats
     * Statistiques qualité : NC ouvertes, actions ouvertes, dérogations + taux interne/externe
     */
    public function ncStats(QualityKPIService $service)
    {
        $stats = $service->getGeneralStatistics();
        $rates = $service->getInternalExternalRates();

        return response()->json(array_merge($stats, [
            'internalNonConformityRate' => round($rates['internalNonConformityRate'], 1),
            'externalNonConformityRate' => round($rates['externalNonConformityRate'], 1),
        ]));
    }

    /**
     * GET /kpi/supplier-delays
     * Délai moyen de réception par fournisseur (trié par délai croissant)
     */
    public function supplierDelays(PurchaseKPIService $service)
    {
        abort_unless(Gate::allows('purchases-menu'), 403);

        return response()->json(
            $service->getAverageReceptionDelayBySupplier()->values()
        );
    }

    /**
     * GET /kpi/mood
     * Humeurs du jour : mon humeur + résumé équipe
     *
     * Shape: {
     *   myMood: 'happy'|'neutral'|'sad'|null,
     *   date:   '2026-04-11',
     *   team:   [{ user_id, name, mood }],
     *   counts: { happy: int, neutral: int, sad: int },
     * }
     */
    public function mood()
    {
        $date = now()->toDateString();

        $teamMoods = Mood::where('date', $date)
            ->with('user:id,name')
            ->get();

        $myMood = $teamMoods->firstWhere('user_id', Auth::id());

        $counts = [
            'happy'   => $teamMoods->where('mood', 'happy')->count(),
            'neutral' => $teamMoods->where('mood', 'neutral')->count(),
            'sad'     => $teamMoods->where('mood', 'sad')->count(),
        ];

        return response()->json([
            'myMood' => $myMood?->mood,
            'date'   => $date,
            'team'   => $teamMoods->map(fn ($m) => [
                'user_id' => $m->user_id,
                'name'    => $m->user?->name ?? '?',
                'mood'    => $m->mood,
            ])->values(),
            'counts' => $counts,
        ]);
    }

    /**
     * POST /kpi/mood
     * Enregistre ou met à jour l'humeur de l'utilisateur connecté pour aujourd'hui
     *
     * Body: { "mood": "happy"|"neutral"|"sad" }
     * Returns: same shape as GET /kpi/mood
     */
    public function setMood(Request $request)
    {
        $request->validate([
            'mood' => ['required', 'in:happy,neutral,sad'],
        ]);

        $date = now()->toDateString();

        Mood::updateOrCreate(
            ['user_id' => Auth::id(), 'date' => $date],
            ['mood' => $request->input('mood')]
        );

        return $this->mood();
    }

    /**
     * GET /kpi/otd
     * Taux de service OTD (On-Time Delivery) — livraisons à l'heure vs total livré
     */
    public function otd(OrderKPIService $service)
    {
        $total  = OrderLines::where('delivery_status', 3)->count();
        $onTime = OrderLines::where('delivery_status', 3)
            ->whereHas('DeliveryLines', function ($q) {
                $q->whereColumn('delivery_lines.created_at', '<=', 'order_lines.delivery_date');
            })->count();

        $rate = $total > 0 ? round(($onTime / $total) * 100, 2) : 0;

        return response()->json([
            'rate'   => $rate,
            'onTime' => $onTime,
            'total'  => $total,
        ]);
    }
}
