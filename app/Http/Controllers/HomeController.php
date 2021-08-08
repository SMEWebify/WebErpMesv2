<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    //
    public function index()
    {
        
        $data['customers_count'] = DB::table('companies')->where('STATU_CLIENT', 2)->count();
        $data['suppliers_count'] = DB::table('companies')->where('STATU_FOUR', 2)->count();
        $data['user_count'] = DB::table('users')->count();
        return view('dashboard')->with('data',$data);;
    }

}
