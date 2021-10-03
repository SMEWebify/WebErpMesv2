<?php

namespace App\Http\Controllers;

use App\Models\Methods\MethodsServices;
use Illuminate\Http\Request;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    //
    public function index()
    {
        $ServiceGoals = MethodsServices::orderBy('ORDRE')->get();
        $LastProducts = Products::orderBy('id', 'desc')->take(5)->get();
        $LastQuotes = Quotes::orderBy('id', 'desc')->take(5)->get();
        $data['customers_count'] = DB::table('companies')->where('STATU_CLIENT', 2)->count();
        $data['suppliers_count'] = DB::table('companies')->where('STATU_FOUR', 2)->count();
        $data['quotes_count'] = DB::table('quotes')->count();
        $data['user_count'] = DB::table('users')->count();

        return view('dashboard', [
            'LastProducts' => $LastProducts,
            'LastQuotes' => $LastQuotes,
            'ServiceGoals' => $ServiceGoals,
        ])->with('data',$data);
    }

}
