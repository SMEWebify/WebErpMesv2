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
        $LastQuotes = Quotes::orderBy('id', 'desc')->take(5)->get();
        //5 lastest Orders add 
        $LastOrders = Orders::orderBy('id', 'desc')->take(5)->get();

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
            'forecastNextThreeMonths' => $forecastNextThreeMonths
        ])->with('data',$data);
    }

}
