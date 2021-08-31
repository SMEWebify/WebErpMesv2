<?php

namespace App\Http\Controllers\Companies;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Companies\Companies;
use App\Http\Requests\Companies\StoreCompanieRequest;

class CompaniesController extends Controller
{
    public function index()
    {

        $Companies = Companies::All();

        return view('companies/companies-index', [
            'Companieslist' => $Companies
        ]);
    }

    public function show($id)
    {

        $Companie = Companies::findOrFail($id);
        

        return view('companies/companies-show', [
            'Companie' => $Companie
        ]);
    }

    public function create()
    {
        $userSelect = User::select('id', 'name')->get();
        return view('companies/companies-create', [
            'userSelect' => $userSelect,
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
                                                    'STATU_CLIENT',
                                                    'DISCOUNT',
                                                    'user_id',
                                                    'COMPTE_GEN_CLIENT',
                                                    'COMPTE_AUX_CLIENT',
                                                    'STATU_FOUR',
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
