<?php

namespace App\Http\Controllers\Companies;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Http\Requests\Companies\StoreCompanieRequest;

class CompaniesController extends Controller
{
    public function index()
    {
        
        //Quote data for chart
        $data['ClientCountRate'] = DB::table('companies')->where('statu_CLIENT', 2)->where('statu_FOUR', '!=', 2)->count();
        $data['ProspectCountRate'] = DB::table('companies')->where('statu_CLIENT', 3)->count();
        $data['SupplierCountRate']= DB::table('companies')->where('statu_FOUR', 2)->where('statu_CLIENT', '!=', 2)->count();
        $data['ClientSupplierCountRate']= DB::table('companies')->where('statu_CLIENT', 2)->where('statu_FOUR', 2)->count();
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

    public function store(StoreCompanieRequest $request)
    {
        $companie = Companies::create($request->only('CODE', 
                                                    'LABEL',
                                                    'WEBSITE',
                                                    'FBSITE',
                                                    'TWITTERSITE', 
                                                    'LKDSITE', 
                                                    'SIREN', 
                                                    'APE', 
                                                    'TVA_INTRA', 
                                                    'TVA_ID', 
                                                    'statu_CLIENT',
                                                    'DISCOUNT',
                                                    'user_id',
                                                    'COMPTE_GEN_CLIENT',
                                                    'COMPTE_AUX_CLIENT',
                                                    'statu_FOUR',
                                                    'COMPTE_GEN_FOUR',
                                                    'COMPTE_AUX_FOUR',
                                                    'RECEPT_CONTROLE',
                                                    'COMMENT', ));
        if($request->hasFile('PICTURE')){
            $path = $request->PICTURE->store('images/companies/','public');
            $companie->update(['PICTURE' => $path]);
        }
        return redirect()->route('companies.show', ['id' => $companie->id])->with('success', 'Successfully created new company');
    }
}
