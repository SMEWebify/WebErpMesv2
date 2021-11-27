<?php

namespace App\Http\Controllers\Companies;

use App\Models\Companies\companiesAddresses;
use App\Http\Requests\Companies\StoreAdressRequest;
use App\Http\Requests\Companies\UpdateAdressRequest;

class AddressesController extends Controller
{
    //
    public function edit($id)
    {
        $adress = companiesAddresses::findOrFail($id);
        return view('companies/addresses-edit', [
            'adress' => $adress
        ]);
    }

    public function store(StoreAdressRequest $request)
    {
        $adress = companiesAddresses::create($request->only('companies_id', 'ORDRE', 'LABEL', 'ADRESS','ZIPCODE','CITY','COUNTRY','NUMBER','MAIL'));
        return redirect()->route('companies.show', ['id' =>  $request->companies_id])->with('success', 'Successfully created adress');
    }

    public function update(UpdateAdressRequest $request)
    {
        $adress = companiesAddresses::find($request->id);
        $adress->ORDRE=$request->ORDRE;
        $adress->LABEL=$request->LABEL;
        $adress->ADRESS=$request->ADRESS;
        $adress->ZIPCODE=$request->ZIPCODE;
        $adress->CITY=$request->CITY;
        $adress->COUNTRY=$request->COUNTRY;
        $adress->NUMBER=$request->NUMBER;
        $adress->MAIL=$request->MAIL;
        $adress->save();
        return redirect()->route('companies.show', ['id' =>  $request->companies_id])->with('success', 'Successfully updated adress');
    }
}
