<?php

namespace App\Http\Controllers\Companies;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;

class CompaniesController extends Controller
{
    public function index()
    {
        
        //Quote data for chart
        $data['ClientCountRate'] = DB::table('companies')->where('statu_customer', '=', 2)->where('statu_supplier', '!=', 2)->count();
        $data['ProspectCountRate'] = DB::table('companies')->where('statu_customer', '=', 3)->count();
        $data['SupplierCountRate']= DB::table('companies')->where('statu_supplier', '=', 2)->where('statu_customer', '!=', 2)->count();
        $data['ClientSupplierCountRate']= DB::table('companies')->where('statu_customer', '=', 2)->where('statu_supplier', 2)->count();
         //5 lastest Orders add 
        $LastComapnies = Companies::orderBy('id', 'desc')->take(5)->get();
        
        return view('companies/companies-index', [
            'LastComapnies' => $LastComapnies
        ])->with('data',$data);;
    }

    public function show($id)
    {
        $Companie = Companies::findOrFail($id);
        return view('companies/companies-show', [
            'Companie' => $Companie
        ]);
    }
}
