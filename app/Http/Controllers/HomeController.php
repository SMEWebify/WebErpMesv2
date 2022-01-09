<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use Illuminate\Support\Facades\DB;
use App\Models\Workflow\OrderLines;
use App\Models\Admin\EstimatedBudgets;
use App\Models\Methods\MethodsServices;

class HomeController extends Controller
{
    //
    
    public function index()
    {
        $CurentYear = Carbon::now()->format('Y');

        //use for liste of tasks
        $ServiceGoals = MethodsServices::withCount('Tasks')->orderBy('ORDRE')->get();
        //5 last product add 
        $LastProducts = Products::orderBy('id', 'desc')->take(5)->get();
        //5 last Quotes add 
        $LastQuotes = Quotes::orderBy('id', 'desc')->take(5)->get();
        /*  
        try to obtain total price of cost
        $LastQuotes = Quotes::select("*", DB::Raw("SUM(Subtotal) AS sub_total"))
                            ->groupBy('seller_id')
                            ->orderBy('id', 'desc')
                            ->take(5)
                            ->get();*/

        //5 lastest Orders add 
        $LastOrders = Orders::orderBy('id', 'desc')->take(5)->get();
        //incoming Order 
        $incomingOrdersCount = OrderLines::orderBy('id', 'desc')
                                            ->where([
                                                ['delivery_date', '>', Carbon::now()],
                                                ['delivery_date', '<', Carbon::now()->addDays(2)],
                                            ])
                                            ->where('delivery_status', '<', 3)
                                            ->groupBy('orders_id')
                                            ->get();
        $incomingOrdersCount = count($incomingOrdersCount)-5;

        $incomingOrders = OrderLines::orderBy('id', 'desc')
                            ->where([
                                ['delivery_date', '>', Carbon::now()],
                                ['delivery_date', '<', Carbon::now()->addDays(2)],
                            ])
                            ->where('delivery_status', '<', 3)
                            ->groupBy('orders_id')
                            ->get();
        //late Order count
        $LateOrdersCount = OrderLines::orderBy('id', 'desc')
                            ->where('delivery_date', '<', Carbon::now())
                            ->where('delivery_status', '<', 3)
                            ->groupBy('orders_id')
                            ->get();
        $LateOrdersCount = count($LateOrdersCount)-5;

        $LateOrders = OrderLines::orderBy('id', 'desc')
                            ->where('delivery_date', '<', Carbon::now())
                            ->where('delivery_status', '<', 3)
                            ->groupBy('orders_id')
                            ->take(5)
                            ->get();
        
        // Display total customers, suppliers, quotes, orders, NC 
        $data['customers_count'] = DB::table('companies')->where('statu_CLIENT', 2)->count();
        $data['suppliers_count'] = DB::table('companies')->where('statu_FOUR', 2)->count();
        $data['quotes_count'] = DB::table('quotes')->count();
        $data['orders_count'] = DB::table('orders')->count();
        $data['quality_non_conformities_count'] = DB::table('quality_non_conformities')->count();
        $data['user_count'] = DB::table('users')->count();
        
        //Quote data for chart
        $data['quotesDataRate'] = DB::table('quotes')
                                    ->select('statu', DB::raw('count(*) as QuoteCountRate'))
                                    ->whereYear('created_at', $CurentYear)
                                    ->groupBy('statu')
                                    ->get();
        //Order data for chart
        $data['orderMonthlyRecap'] = DB::table('order_lines')
                                    ->selectRaw('
                                        MONTH(delivery_date) AS month,
                                        SUM((selling_price * qty)-(selling_price * qty)*(discount/100)) AS orderSum
                                    ')
                                    ->whereYear('created_at', $CurentYear)
                                    ->groupByRaw('MONTH(delivery_date) ')
                                    ->get();
        //Estimated Budgets data for chart
        $data['estimatedBudget'] = EstimatedBudgets::where('year', $CurentYear)->get();
        
        return view('dashboard', [
            'LastProducts' => $LastProducts,
            'LastQuotes' => $LastQuotes,
            'LastOrders' =>  $LastOrders,
            'LateOrdersCount' =>  $LateOrdersCount,
            'incomingOrders' =>  $incomingOrders,
            'incomingOrdersCount' => $incomingOrdersCount,
            'LateOrders' =>  $LateOrders,
            'ServiceGoals' => $ServiceGoals,
        ])->with('data',$data);
    }

}
