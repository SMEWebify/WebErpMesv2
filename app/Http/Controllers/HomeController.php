<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use Illuminate\Support\Facades\DB;
use App\Models\Methods\MethodsServices;

class HomeController extends Controller
{
    //
    public function index()
    {
        $ServiceGoals = MethodsServices::orderBy('ORDRE')->get();
        $LastProducts = Products::orderBy('id', 'desc')->take(5)->get();
        $LastQuotes = Quotes::orderBy('id', 'desc')->take(5)->get();
        $LastOrders = Orders::orderBy('id', 'desc')->take(5)->get();
      /*  $LastQuotes = Quotes::select("*", DB::Raw("SUM(Subtotal) AS sub_total"))
                            ->groupBy('seller_id')
                            ->orderBy('id', 'desc')
                            ->take(5)
                            ->get();*/

        $data['customers_count'] = DB::table('companies')->where('statu_CLIENT', 2)->count();
        $data['suppliers_count'] = DB::table('companies')->where('statu_FOUR', 2)->count();
        $data['quotes_count'] = DB::table('quotes')->count();
        $data['orders_count'] = DB::table('orders')->count();
        $data['quality_non_conformities_count'] = DB::table('quality_non_conformities')->count();
        $data['user_count'] = DB::table('users')->count();
        $data['quotesDataRate'] = DB::table('quotes')
                                    ->select('statu', DB::raw('count(*) as QuoteCountRate'))
                                    ->groupBy('statu')
                                    ->get();
        $data['orderMonthlyRecap'] = DB::table('order_lines')
                                    ->selectRaw('
                                        MONTH(delivery_date) AS month,
                                        SUM((selling_price * qty)-(selling_price * qty)*(discount/100)) AS orderSum
                                    ')
                                    ->groupByRaw('MONTH(delivery_date) ')
                                    ->get();
        
        return view('dashboard', [
            'LastProducts' => $LastProducts,
            'LastQuotes' => $LastQuotes,
            'LastOrders' =>  $LastOrders,
            'ServiceGoals' => $ServiceGoals,
        ])->with('data',$data);
    }

}
