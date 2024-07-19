<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use App\Models\Admin\Announcements;
use Illuminate\Support\Facades\DB;
use App\Models\Workflow\OrderLines;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin\EstimatedBudgets;
use App\Models\Methods\MethodsServices;

class HomeController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $CurentYear = Carbon::now()->format('Y');
        $CurentMonth = Carbon::now()->format('m');

        // check if user had role
        $user = User::find(Auth::user()->id);
        $userRoleCount = $user->getRoleNames()->count();

        // Display total customers, suppliers, quotes, orders, NC 
        $all_count = DB::table('users')->selectRaw("'user_count' as type, count(*) as count")
            ->unionAll(DB::table('companies')->selectRaw("'customers_count' as type, count(*) as count")->where('statu_customer', '=', '2')->whereYear('created_at', '=', $CurentYear))
            ->unionAll(DB::table('companies')->selectRaw("'suppliers_count' as type, count(*) as count")->where('statu_supplier', '=', '2'))
            ->unionAll(DB::table('quotes')->selectRaw("'quotes_count' as type, count(*) as count"))
            ->unionAll(DB::table('orders')->selectRaw("'orders_count' as type, count(*) as count"))
            ->unionAll(DB::table('quality_non_conformities')->selectRaw("'quality_non_conformities_count' as type, count(*) as count"))
            ->get();
        $data = $all_count->reduce(function($data, $count) {
            $data[$count->type] = $count->count;
            return $data;
        }, []);

        $Announcement = Announcements::latest()->first();

        //Estimated Budgets data for chart
        $data['estimatedBudget'] = EstimatedBudgets::where('year', $CurentYear)->get();
        if(count($data['estimatedBudget']) == 0){
            return redirect()->route('admin.factory')->with('error', 'Please check estimated budgets');
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
        $data['orderMonthlyRecap'] = DB::table('order_lines')
                                                            ->selectRaw('
                                                                MONTH(delivery_date) AS month,
                                                                SUM((selling_price * qty)-(selling_price * qty)*(discount/100)) AS orderSum
                                                            ')
                                                            ->leftJoin('orders', function($join) {
                                                                $join->on('order_lines.orders_id', '=', 'orders.id')
                                                                    ->where('orders.type', '=', 1);
                                                            })
                                                            ->whereYear('order_lines.created_at', $CurentYear)
                                                            ->groupByRaw('MONTH(order_lines.delivery_date) ')
                                                            ->get();

        //Delivery data for chart
        $data['deliveryMonthlyRecap'] = DB::table('delivery_lines')->join('order_lines', 'delivery_lines.order_line_id', '=', 'order_lines.id')
                                                                    ->selectRaw('
                                                                        MONTH(delivery_lines.created_at) AS month,
                                                                        SUM((order_lines.selling_price * delivery_lines.qty)-(order_lines.selling_price * delivery_lines.qty)*(order_lines.discount/100)) AS orderSum
                                                                    ')
                                                                    ->whereYear('delivery_lines.created_at', $CurentYear)
                                                                    ->groupByRaw('MONTH(delivery_lines.created_at) ')
                                                                    ->get();

        //Invoices data for chart
        $data['invoiceMonthlyRecap'] = DB::table('invoice_lines')
                                                            ->join('order_lines', 'invoice_lines.order_line_id', '=', 'order_lines.id')
                                                            ->selectRaw('
                                                                MONTH(invoice_lines.created_at) AS month,
                                                                SUM((order_lines.selling_price * invoice_lines.qty)-(order_lines.selling_price * invoice_lines.qty)*(order_lines.discount/100)) AS orderSum
                                                            ')
                                                            ->whereYear('invoice_lines.created_at', $CurentYear)
                                                            ->groupByRaw('MONTH(invoice_lines.created_at) ')
                                                            ->get();

        //Total ForCast
        $orderTotalForCast = DB::table('order_lines') ->selectRaw('
                                                                ROUND(SUM((selling_price * qty)-(selling_price * qty)*(discount/100)),2) AS orderTotalForCast
                                                        ')
                                                        ->leftJoin('orders', function($join) {
                                                            $join->on('order_lines.orders_id', '=', 'orders.id')
                                                                ->where('orders.type', '=', 1);
                                                        })
                                                        ->where('delivery_status', '=', 1)
                                                        ->orwhere('delivery_status', '=', 2)
                                                        ->whereYear('order_lines.delivery_date', $CurentYear)
                                                        ->get();

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
        // we use in future deadline trait for this
        $incomingOrdersCount = OrderLines::orderBy('id', 'desc') ->where([
                                                                ['delivery_date', '>', Carbon::now()],
                                                                ['delivery_date', '<', Carbon::now()->addDays(2)],
                                                            ])
                                                            ->where('delivery_status', '<', 3)
                                                            ->groupBy('orders_id')
                                                            ->get();
        $incomingOrdersCount = count($incomingOrdersCount)-4;

        $incomingOrders = OrderLines::orderBy('id', 'desc')->where([
                                                                    ['delivery_date', '>', Carbon::now()],
                                                                    ['delivery_date', '<', Carbon::now()->addDays(2)],
                                                            ])
                                                            ->where('delivery_status', '<', 3)
                                                            ->groupBy('orders_id')
                                                            ->take(10)
                                                            ->get();
        //late Order count
        $LateOrdersCount = OrderLines::orderBy('id', 'desc')->where('delivery_date', '<', Carbon::now())
                                                            ->where('delivery_status', '<', 3)
                                                            ->groupBy('orders_id')
                                                            ->get();
        $LateOrdersCount = count($LateOrdersCount)-4;

        $LateOrders = OrderLines::orderBy('id', 'desc')->where('delivery_date', '<', Carbon::now())
                                                        ->where('delivery_status', '<', 3)
                                                        ->groupBy('orders_id')
                                                        ->take(10)
                                                        ->get();

        //Quote data for chart
        $data['quotesDataRate'] = DB::table('quotes')
                                    ->select('statu', DB::raw('count(*) as QuoteCountRate'))
                                    ->whereYear('created_at', $CurentYear)
                                    ->groupBy('statu')
                                    ->get();
        //5 last Quotes add 
        $LastQuotes = Quotes::orderBy('id', 'desc')->take(5)->get();
        //5 lastest Orders add 
        $LastOrders = Orders::orderBy('id', 'desc')->take(5)->get();

        //use for liste of tasks
        $ServiceGoals = MethodsServices::withCount(['Tasks', 'Tasks' => function ($query) {
                                            $query->whereNotNull('order_lines_id');
                                        }])
                                        ->orderBy('ordre')->get();
        $Tasks = DB::table('tasks')
                    ->select('tasks.id','statuses.title', 'methods_services.id as methods_id', 'methods_services.label', DB::raw('count(*) as total_task'))
                    ->join('statuses', 'tasks.status_id', '=', 'statuses.id')
                    ->join('methods_services', 'tasks.methods_services_id', '=', 'methods_services.id')
                    ->whereNotNull('tasks.order_lines_id')
                    ->groupBy('methods_services_id')
                    ->groupBy('status_id')
                    ->orderBy('statuses.order', 'asc')
                    ->get();

        //5 last product add 
        $LastProducts = Products::orderBy('id', 'desc')->take(6)->get();

        //total price
        $data['delivered_month_in_progress'] = DB::table('delivery_lines')
                                                ->join('order_lines', 'delivery_lines.order_line_id', '=', 'order_lines.id')
                                                ->selectRaw('FLOOR(SUM((order_lines.selling_price * order_lines.qty)-(order_lines.selling_price * order_lines.qty)*(order_lines.discount/100))) AS orderSum')
                                                ->whereYear('delivery_lines.created_at', '=', $CurentYear)
                                                ->whereMonth('delivery_lines.created_at', $CurentMonth)
                                                ->get();
                                                
        $data['remaining_order'] =  DB::table('order_lines')
                                                ->selectRaw('
                                                    FLOOR(SUM((selling_price * qty)-(selling_price * qty)*(discount/100))) AS orderSum
                                                ')
                                                ->whereYear('delivery_date', '=', $CurentYear)
                                                ->whereMonth('delivery_date', $CurentMonth)
                                                ->groupByRaw('MONTH(delivery_date) ')
                                                ->get();

        return view('dashboard', [
            'userRoleCount' => $userRoleCount,
            'Announcement' => $Announcement,
            'LastProducts' => $LastProducts,
            'LastQuotes' => $LastQuotes,
            'LastOrders' =>  $LastOrders,
            'OrderTotalForCast' =>  $orderTotalForCast,
            'orderTotalDelivered' =>  $orderTotalDelivered ,
            'orderTotaInvoiced' =>  $orderTotaInvoiced,
            'LateOrdersCount' =>  $LateOrdersCount,
            'incomingOrders' =>  $incomingOrders,
            'incomingOrdersCount' => $incomingOrdersCount,
            'LateOrders' =>  $LateOrders,
            'ServiceGoals' => $ServiceGoals,
            'Tasks' => $Tasks,
            'EstimatedBudgets' => $EstimatedBudgets,
        ])->with('data',$data);
    }

}
