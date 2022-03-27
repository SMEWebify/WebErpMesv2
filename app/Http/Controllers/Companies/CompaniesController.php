<?php

namespace App\Http\Controllers\Companies;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Http\Requests\Companies\UpdateCompanieRequest;

class CompaniesController extends Controller
{
    public function index()
    {
        
        //Quote data for chart
        $data['ClientCountRate'] = DB::table('companies')->where('statu_customer', '=', 2)->where('statu_supplier', '!=', 2)->count();
        $data['ProspectCountRate'] = DB::table('companies')->where('statu_customer', '=', 3)->count();
        $data['SupplierCountRate']= DB::table('companies')->where('statu_supplier', '=', 2)->where('statu_customer', '!=', 2)->count();
        $data['ClientSupplierCountRate']= DB::table('companies')->where('statu_customer', '=', 2)->where('statu_supplier', 2)->count();
         //5 lastest Companies add 
        $LastComapnies = Companies::orderByRaw('id', 'desc')->take(5)->get();
        
        return view('companies/companies-index', [
            'LastComapnies' => $LastComapnies
        ])->with('data',$data);;
    }

    public function show($id)
    {
        $Companie = Companies::findOrFail($id);
        $userSelect = User::select('id', 'name')->get();
        return view('companies/companies-show', [
            'Companie' => $Companie,
            'userSelect' => $userSelect,
        ]);
    }

    public function update(UpdateCompanieRequest $request)
    {
        $Companie = Companies::findOrFail($request->id);
        $Companie->label  =$request->label;
        $Companie->website =$request->website;
        $Companie->fbsite  =$request->fbsite;
        $Companie->twittersite  =$request->twittersite; 
        $Companie->lkdsite = $request->lkdsite; 
        $Companie->siren = $request->siren; 
        $Companie->naf_code = $request->naf_code; 
        $Companie->intra_community_vat =$request->intra_community_vat; 
        $Companie->statu_customer = $request->statu_customer;
        $Companie->discount =$request->discount;
        $Companie->user_id =$request->user_id;
        $Companie->account_general_customer =$request->account_general_customer;
        $Companie->account_auxiliary_customer =$request->account_auxiliary_customer;
        $Companie->statu_supplier =$request->statu_supplier;
        $Companie->account_general_supplier =$request->account_general_supplier;
        $Companie->account_auxiliary_supplier =$request->account_auxiliary_supplier;
        $Companie->recept_controle =$request->recept_controle;
        $Companie->comment =$request->comment;
        $Companie->save();
        return redirect()->route('companies.show', ['id' =>  $Companie->id])->with('success', 'Successfully updated companie');
    }
}
