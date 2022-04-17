<?php

namespace App\Http\Controllers\Companies;

use App\Models\Companies\CompaniesAddresses;
use App\Http\Requests\Companies\StoreAdressRequest;
use App\Http\Requests\Companies\UpdateAdressRequest;

class AddressesController extends Controller
{
    /**
     * @param $id
     * @return View
     */
    public function edit($id)
    {
        $adress = CompaniesAddresses::findOrFail($id);
        return view('companies/addresses-edit', [
            'adress' => $adress
        ]);
    }

    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreAdressRequest $request)
    {
        $adress = CompaniesAddresses::create($request->only('companies_id', 'ordre', 'label', 'adress','zipcode','city','country','number','mail'));
        return redirect()->route('companies.show', ['id' =>  $request->companies_id])->with('success', 'Successfully created adress');
    }

    /**
     * @param Request $request
     * @return View
     */
    public function update(UpdateAdressRequest $request)
    {
        $adress = CompaniesAddresses::find($request->id);
        $adress->ordre=$request->ordre;
        $adress->label=$request->label;
        $adress->adress=$request->adress;
        $adress->zipcode=$request->zipcode;
        $adress->city=$request->city;
        $adress->country=$request->country;
        $adress->number=$request->number;
        $adress->mail=$request->mail;
        $adress->save();
        return redirect()->route('companies.show', ['id' =>  $request->companies_id])->with('success', 'Successfully updated adress');
    }
}
