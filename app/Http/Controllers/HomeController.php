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

        $data['customers_count'] = DB::table('companies')->where('STATU_CLIENT', 2)->count();
        $data['suppliers_count'] = DB::table('companies')->where('STATU_FOUR', 2)->count();
        $data['quotes_count'] = DB::table('quotes')->count();
        $data['orders_count'] = DB::table('orders')->count();
        $data['quality_non_conformities_count'] = DB::table('quality_non_conformities')->count();
        $data['user_count'] = DB::table('users')->count();

        return view('dashboard', [
            'LastProducts' => $LastProducts,
            'LastQuotes' => $LastQuotes,
            'LastOrders' =>  $LastOrders,
            'ServiceGoals' => $ServiceGoals,
        ])->with('data',$data);
    }

}
