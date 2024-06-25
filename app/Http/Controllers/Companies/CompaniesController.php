<?php

namespace App\Http\Controllers\Companies;

use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\NextPreviousTrait;
use App\Services\ImportCsvService;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Services\SelectDataService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Companies\UpdateCompanieRequest;

class CompaniesController extends Controller
{
    use NextPreviousTrait;
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * @return View
     */
    public function index()
    {
        
        //Quote data for chart
        $data['ClientCountRate'] = DB::table('companies')->where('statu_customer', '=', 2)->where('statu_supplier', '!=', 2)->count();
        $data['ProspectCountRate'] = DB::table('companies')->where('statu_customer', '=', 3)->count();
        $data['SupplierCountRate']= DB::table('companies')->where('statu_supplier', '=', 2)->where('statu_customer', '!=', 2)->count();
        $data['ClientSupplierCountRate']= DB::table('companies')->where('statu_customer', '=', 2)->where('statu_supplier', 2)->count();
         //5 lastest Companies add 
        $LastComapnies = Companies::orderBy('id', 'desc')->take(5)->get();
        
        return view('companies/companies-index', [
            'LastComapnies' => $LastComapnies
        ])->with('data',$data);;
    }

    /**
     * @param $id
     * @return View
     */
    public function show($id)
    {
        $Companie = Companies::findOrFail($id);
        $userSelect = $this->SelectDataService->getUsers();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Companies(), $Companie->id);

        return view('companies/companies-show', [
            'Companie' => $Companie,
            'userSelect' => $userSelect,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
        ]);
    }

    public function import(Request $request, ImportCsvService $importCsvService)
    {   
        $importCsvService->importCompanies($request);
        return redirect()->back();
    }

    /**
     * @param $id
     * @return View
     */
    public function update(UpdateCompanieRequest $request)
    {
        $Companie = Companies::findOrFail($request->id);
        $Companie->civility  =$request->civility;
        $Companie->label  =$request->label;
        $Companie->last_name  =$request->last_name;
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
        if($request->active) $Companie->active=1;
        else $Companie->active = 0;
        $Companie->barcode_value =$request->barcode_value;
        $Companie->save();
        return redirect()->route('companies.show', ['id' =>  $Companie->id])->with('success', 'Successfully updated companie');
    }
}
