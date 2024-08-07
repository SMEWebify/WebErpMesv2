<?php

namespace App\Http\Controllers\Companies;

use App\Models\Companies\CompaniesAddresses;
use App\Http\Requests\Companies\StoreAdressRequest;
use App\Http\Requests\Companies\UpdateAdressRequest;

class AddressesController extends Controller
{
    /**
     * Store a newly created address in storage.
     *
     * @param \App\Http\Requests\Companies\StoreAdressRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreAdressRequest $request)
    {
        $adress = CompaniesAddresses::create($request->validated());
        return redirect()->route('companies.show', ['id' =>  $request->companies_id])->with('success', 'Successfully created adress');
    }

    /**
     * Update the specified address in storage.
     *
     * @param \App\Http\Requests\Companies\UpdateAdressRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateAdressRequest $request)
    {
        $adress = CompaniesAddresses::findOrFail($request->id);
        $adress->update($request->validated()); 
        if($request->defaultAdress_update) $adress->default=1;
        else $adress->default = 0;
        $adress->save();
        return redirect()->route('companies.show', ['id' =>  $request->companies_id])->with('success', 'Successfully updated adress');
    }
}
