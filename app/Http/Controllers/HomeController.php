<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Number;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use App\Services\OrderKPIService;
use App\Services\QuoteKPIService;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\Announcements;
use App\Services\InvoiceKPIService;
use App\Services\OrderLinesService;
use App\Services\DeliveryKPIService;
use App\Services\PurchaseKPIService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\Admin\EstimatedBudgets;
use App\Models\Methods\MethodsServices;

class HomeController extends Controller
{

    protected $orderKPIService;
    protected $deliveryKPIService;
    protected $quoteKPIService;
    protected $invoiceKPIService;
    protected $orderLinesService;
    protected $purchaseKPIService;

    public function __construct(
                                OrderKPIService $orderKPIService, 
                                DeliveryKPIService $deliveryKPIService,
                                QuoteKPIService $quoteKPIService,
                                InvoiceKPIService $invoiceKPIService,
                                OrderLinesService $orderLinesService,
                                PurchaseKPIService $purchaseKPIService,
                        ){
                            $this->orderKPIService = $orderKPIService;
                            $this->deliveryKPIService = $deliveryKPIService;
                            $this->quoteKPIService = $quoteKPIService;
                            $this->invoiceKPIService = $invoiceKPIService;
                            $this->orderLinesService = $orderLinesService;
                            $this->purchaseKPIService = $purchaseKPIService;
                        }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $factory = app('Factory');
        $CurentYear = Carbon::now()->format('Y');
        $CurentMonth = Carbon::now()->format('m');

        // check if user had role
        $user = User::find(Auth::user()->id);
        $userRoleCount = $user->getRoleNames()->count();

        // Display total customers, suppliers, quotes, orders, NC 
        $all_count = DB::table('users')->selectRaw("'user_count' as type, count(*) as count")
            ->unionAll(DB::table('companies')->selectRaw("'customers_count' as type, count(*) as count")->where('statu_customer', '=', '2')->whereYear('created_at', '=', $CurentYear))
            ->unionAll(DB::table('companies')->selectRaw("'suppliers_count' as type, count(*) as count")->where('statu_supplier', '=', '2'))
            ->unionAll(DB::table('quotes')->selectRaw("'quotes_count' as type, count(*) as count")->where('statu', 1))
            ->unionAll(DB::table('orders')->selectRaw("'orders_count' as type, count(*) as count")->where('statu', 1))
            ->unionAll(DB::table('quality_non_conformities')->selectRaw("'quality_non_conformities_count' as type, count(*) as count"))
            ->get();
        $data = $all_count->reduce(function($data, $count) {
            $data[$count->type] = $count->count;
            return $data;
        }, []);
        $latestCustomerCreatedAt = DB::table('companies')
            ->where('statu_customer', '=', '2')
            ->orderByDesc('created_at')
            ->value('created_at');
        $data['latest_customer_since'] = $latestCustomerCreatedAt
            ? Carbon::parse($latestCustomerCreatedAt)->diffForHumans()
            : __('general_content.not_available_trans_key');

        $data['current_month_delivery_notes_count'] = DB::table('deliverys')
            ->whereYear('created_at', '=', $CurentYear)
            ->whereMonth('created_at', '=', $CurentMonth)
            ->count();

        $Announcement = Announcements::latest()->first();

        //Estimated Budgets data for chart
        $data['estimatedBudget'] = EstimatedBudgets::where('year', $CurentYear)->get();
        if(count($data['estimatedBudget']) == 0){
            return redirect()->route('admin.estimated.budgets.settings')->with('error', 'Please check estimated budgets');
        }

        //GOAL Chart
        $EstimatedBudgets = $data['estimatedBudget'][0]->amount1
                            +$data['estimatedBudget'][0]->amount2
                            +$data['estimatedBudget'][0]->amount3
                            +$data['estimatedBudget'][0]->amount4
                            +$data['estimatedBudget'][0]->amount5
                            +$data['estimatedBudget'][0]->amount6
                            +$data['estimatedBudget'][0]->amount7
                            +$data['estimatedBudget'][0]->amount8
                            +$data['estimatedBudget'][0]->amount9
                            +$data['estimatedBudget'][0]->amount10
                            +$data['estimatedBudget'][0]->amount11
                            +$data['estimatedBudget'][0]->amount12;

        //Order data for chart
        $data['orderMonthlyRecap'] = $this->orderKPIService->getOrderMonthlyRecap($CurentYear);

        //Delivery data for chart
        $data['deliveryMonthlyRecap'] = $this->deliveryKPIService->getDeliveryMonthlyRecap($CurentYear);

        //Invoices data for chart
        $data['invoiceMonthlyRecap'] = $this->invoiceKPIService->getInvoiceMonthlyRecap($CurentYear);

        //Total Purchase data for chart
        $data['purchaseMonthlyRecap'] = $this->purchaseKPIService->getPurchaseMonthlyRecap($CurentYear);
        
        //Total ForCast
        $orderTotalForCast = $this->orderKPIService->getOrderTotalForCast($CurentYear);

        //Total Delivered
        $orderTotalDelivered =0;
        foreach ($data['deliveryMonthlyRecap'] as $key => $item){
            $orderTotalDelivered += $item->orderSum;
        }

        //Total Invoiced
        $orderTotaInvoiced =0;
        foreach ($data['invoiceMonthlyRecap'] as $key => $item){
            $orderTotaInvoiced += $item->orderSum;
        }

        //Order incoming end date
        $incomingOrders      = $this->orderLinesService->getIncomingOrders();
        $incomingOrdersCount = $this->orderLinesService->getIncomingOrdersCount();
        $lateOrders          = $this->orderLinesService->getLateOrders();
        $lateOrdersCount     = $this->orderLinesService->getLateOrdersCount();

        //Quote data for chart
        $data['quotesDataRate'] = $this->quoteKPIService->getQuotesDataRate($CurentYear);

        //5 last Quotes add
        $LastQuotes = Quotes::with('companie')->orderBy('id', 'desc')->take(8)->get();
        //8 lastest Orders add
        $LastOrders = Orders::with('companie')->orderBy('id', 'desc')->take(8)->get();

        //use for liste of tasks
        $ServiceGoals = MethodsServices::withCount(['Tasks', 'Tasks' => function ($query) {
                                            $query->whereNotNull('order_lines_id');
                                        }])
                                        ->orderBy('ordre')->get();

        $currency = $factory->curency ?? 'EUR';

        //total price
        $deliveredMonthInProgress = $this->deliveryKPIService->getDeliveryMonthlyProgress($CurentMonth, $CurentYear);
        $deliveredMonthInProgress = Number::currency($deliveredMonthInProgress->orderSum ?? 0, $currency, config('app.locale'));
                                                
        $remainingDeliveryOrder =   $this->orderKPIService->getOrderMonthlyRemainingToDelivery($CurentMonth, $CurentYear);
        $remainingInvoiceOrder =   $this->orderKPIService->getOrderMonthlyRemainingToInvoiceByMonth($CurentMonth, $CurentYear);
        $forecastNextThreeMonths = $this->orderKPIService->getOrderForecastNextMonths(3, Carbon::create($CurentYear, $CurentMonth, 1));

        $orderTotalFormattedDelivered =   Number::currency($orderTotalDelivered ?? 0, $currency, config('app.locale'));
        $orderTotalFormattedInvoiced =   Number::currency($orderTotaInvoiced ?? 0, $currency, config('app.locale'));
        $FormattedEstimatedBudgets =   Number::currency($EstimatedBudgets ?? 0, $currency, config('app.locale'));
        $remainingDeliveryOrder =   Number::currency($remainingDeliveryOrder->orderSum ?? 0, $currency, config('app.locale'));
        $remainingInvoiceOrder =   Number::currency($remainingInvoiceOrder->orderSum ?? 0, $currency, config('app.locale'));
        $forecastNextThreeMonths =   Number::currency($forecastNextThreeMonths->orderSum ?? 0, $currency, config('app.locale'));

        // ── Props React HomeDashboard ────────────────────────────────────────
        $locale   = config('app.locale', 'fr');
        $intlLocale = match($locale) { 'fr' => 'fr-FR', 'en' => 'en-GB', 'es' => 'es-ES', default => 'fr-FR' };
        $monthNames = __('general_content.chart_months_trans_key');
        $monthKeys  = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];

        $serializeOrderLine = fn ($line) => [
            'orders_id'     => $line->orders_id,
            'delivery_date' => $line->delivery_date,
            'order'         => $line->order ? ['id' => $line->order->id, 'code' => $line->order->code] : null,
        ];

        $reactProps = [
            'year'     => (int) $CurentYear,
            'currency' => $currency,
            'locale'   => $intlLocale,
            'canPurchases' => Gate::allows('purchases-menu'),

            'kpi' => [
                'customers_count'          => $data['customers_count'],
                'latest_customer_since'    => __('general_content.since_time_trans_key', ['time' => $data['latest_customer_since']]),
                'suppliers_count'          => $data['suppliers_count'],
                'delivery_notes_count'     => $data['current_month_delivery_notes_count'],
                'quotes_count'             => $data['quotes_count'],
                'orders_count'             => $data['orders_count'],
                'delivered_month'          => $deliveredMonthInProgress,
                'remaining_delivery'       => $remainingDeliveryOrder,
                'remaining_invoice'        => $remainingInvoiceOrder,
                'forecast_3m'              => $forecastNextThreeMonths,
                'order_total_forecast_raw' => $orderTotalForCast[0]->orderTotalForCast ?? 0,
                'invoiced_raw'             => $orderTotaInvoiced,
                'target_raw'               => $EstimatedBudgets,
            ],

            'charts' => [
                'orderMonthlyRecap'    => $data['orderMonthlyRecap'],
                'deliveryMonthlyRecap' => $data['deliveryMonthlyRecap'],
                'invoiceMonthlyRecap'  => $data['invoiceMonthlyRecap'],
                'purchaseMonthlyRecap' => $data['purchaseMonthlyRecap'],
                'estimatedBudget'      => $data['estimatedBudget'],
                'quotesDataRate'       => $data['quotesDataRate'],
            ],

            'delivery' => [
                'incomingOrders'      => $incomingOrders->map($serializeOrderLine)->values(),
                'incomingOrdersCount' => $incomingOrdersCount,
                'lateOrders'          => $lateOrders->map($serializeOrderLine)->values(),
                'lateOrdersCount'     => $lateOrdersCount,
            ],

            'recentOrders' => $LastOrders->map(fn ($o) => [
                'id'                    => $o->id,
                'code'                  => $o->code,
                'statu'                 => $o->statu,
                'type'                  => $o->type,
                'companies_id'          => $o->companies_id,
                'companie_label'        => $o->companie?->label,
                'formatted_total_price' => $o->formatted_total_price,
                'validity_date'         => $o->validity_date,
            ])->values(),

            'recentQuotes' => $LastQuotes->map(fn ($q) => [
                'id'                    => $q->id,
                'code'                  => $q->code,
                'statu'                 => $q->statu,
                'companies_id'          => $q->companies_id,
                'companie_label'        => $q->companie?->label,
                'formatted_total_price' => $q->formatted_total_price,
                'created_at_human'      => $q->GetPrettyCreatedAttribute(),
            ])->values(),

            'announcement' => $Announcement ? [
                'title'   => $Announcement->title,
                'comment' => $Announcement->comment,
            ] : null,

            'urls' => [
                'orders_index'   => route('orders'),
                'orders_show'    => route('orders') . '/',
                'quotes_index'   => route('quotes'),
                'quotes_show'    => route('quotes') . '/',
                'companies_show' => route('companies') . '/',
                'deliveries'     => route('deliverys'),
                'invoices'       => route('invoices'),
                'calendar'       => route('production.calendar.orders'),
            ],

            'endpoints' => [
                'quote_rate'     => route('kpi.quotes.rate'),
                'orders_monthly' => route('kpi.orders.monthly'),
                'delivery_board' => route('kpi.delivery.board'),
                'recent_orders'  => route('kpi.recent.orders'),
                'recent_quotes'    => route('kpi.recent.quotes'),
                'dashboard_config' => route('dashboard.config.show'),
            ],

            'trans' => array_merge(
                // mois
                array_combine($monthKeys, is_array($monthNames) ? $monthNames : array_fill(0, 12, '')),
                [
                    // statuts commandes
                    'open'             => __('general_content.open_trans_key'),
                    'in_progress'      => __('general_content.in_progress_trans_key'),
                    'delivered'        => __('general_content.delivered_trans_key'),
                    'partly_delivered' => __('general_content.partly_delivered_trans_key'),
                    'stopped'          => __('general_content.stopped_trans_key'),
                    'canceled'         => __('general_content.canceled_trans_key'),
                    // statuts devis
                    'send'             => __('general_content.send_trans_key'),
                    'win'              => __('general_content.win_trans_key'),
                    'lost'             => __('general_content.lost_trans_key'),
                    'closed'           => __('general_content.closed_trans_key'),
                    'obsolete'         => __('general_content.obsolete_trans_key'),
                    // stat cards
                    'new_client'         => __('general_content.new_client_trans_key'),
                    'suppliers'          => __('general_content.suppliers_trans_key'),
                    'quotes_open'        => __('general_content.quote_trans_key') . ' ' . __('general_content.open_trans_key'),
                    'orders_open'        => __('general_content.orders_trans_key') . ' ' . __('general_content.open_trans_key'),
                    'delivered_month'    => __('general_content.delivered_month_in_progress_trans_key'),
                    'remaining_delivery' => __('general_content.remaining_month_trans_key'),
                    'remaining_invoice'  => __('general_content.remaining_invoice_month_trans_key'),
                    'forecast_3m'        => __('general_content.forecast_next_three_months_trans_key'),
                    'view_details'       => __('general_content.view_details_trans_key'),
                    'delivery_notes'     => __('general_content.delivery_notes_trans_key'),
                    'current_month'      => __('general_content.current_month_trans_key'),
                    // delivery board
                    'order_to_be_delivered' => __('general_content.order_to_be_delivered_trans_key'),
                    'incoming_orders'       => __('general_content.incoming_orders_trans_key'),
                    'late_orders'           => __('general_content.late_orders_trans_key'),
                    'no_coming_orders'      => __('general_content.no_coming_orders_trans_key'),
                    'no_late_orders'        => __('general_content.no_late_orders_trans_key'),
                    // graphique mensuel
                    'monthly_recap_title'    => __('general_content.monthly_recap_report_trans_key'),
                    'orders_forecast'        => __('general_content.chart_order_forecast_trans_key'),
                    'delivered_revenues'     => __('general_content.chart_delivered_revenues_trans_key'),
                    'invoiced_revenues'      => __('general_content.chart_invoiced_revenues_trans_key'),
                    'purchase_revenues'      => __('general_content.chart_purchase_revenues_trans_key'),
                    'order_targets'          => __('general_content.chart_order_targets_trans_key'),
                    'total_order_forecasted' => __('general_content.total_order_forcasted_trans_key'),
                    'total_order_delivered'  => __('general_content.total_order_delivered_trans_key'),
                    'total_invoiced'         => __('general_content.total_invoiced_trans_key'),
                    // objectif
                    'goal'          => __('general_content.goal_trans_key'),
                    'annual_target' => __('general_content.total_order_forcasted_trans_key'),
                    'remaining'     => __('general_content.remaining_month_trans_key'),
                    // tableau récents
                    'latest_orders'  => __('general_content.latest_orders_trans_key'),
                    'latest_quotes'  => __('general_content.latest_quotes_trans_key'),
                    'no_order'       => __('general_content.no_order_trans_key'),
                    'no_quote'       => __('general_content.no_quote_trans_key'),
                    'view_all'       => __('general_content.view_all_trans_key'),
                    'internal_order' => __('general_content.internal_order_trans_key'),
                    // taux devis
                    'quote_rate_title' => __('general_content.quote_transformation_trans_key'),
                    'total'            => 'Total',
                    'no_quotes'        => __('general_content.no_quote_trans_key'),
                    // annonce
                    'announcement'     => __('general_content.announcement_trans_key'),
                    'no_announcement'  => 'No announcement',
                ]
            ),
        ];

        return view('dashboard', [
            'userRoleCount' => $userRoleCount,
            'Announcement' => $Announcement,
            'LastQuotes' => $LastQuotes,
            'LastOrders' =>  $LastOrders,
            'OrderTotalForCast' =>  $orderTotalForCast,
            'orderTotalDelivered' =>  $orderTotalDelivered,
            'orderTotaInvoiced' =>  $orderTotaInvoiced,
            'orderTotalFormattedDelivered' =>  $orderTotalFormattedDelivered,
            'orderTotalFormattedInvoiced' =>  $orderTotalFormattedInvoiced,
            'lateOrdersCount' =>  $lateOrdersCount,
            'incomingOrders' =>  $incomingOrders,
            'incomingOrdersCount' => $incomingOrdersCount,
            'lateOrders' =>  $lateOrders,
            'ServiceGoals' => $ServiceGoals,
            'EstimatedBudgets' => $EstimatedBudgets,
            'FormattedEstimatedBudgets' => $FormattedEstimatedBudgets,
            'deliveredMonthInProgress' => $deliveredMonthInProgress,
            'remainingDeliveryOrder' => $remainingDeliveryOrder,
            'remainingInvoiceOrder' => $remainingInvoiceOrder,
            'forecastNextThreeMonths' => $forecastNextThreeMonths,
            'reactProps' => $reactProps,
        ])->with('data',$data);
    }

}
