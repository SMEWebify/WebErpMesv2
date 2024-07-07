<?php

namespace App\Http\Controllers\Companies;

use App\Models\Companies\CompaniesAddresses;
use App\Http\Requests\Companies\StoreAdressRequest;
use App\Http\Requests\Companies\UpdateAdressRequest;

class AddressesController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreAdressRequest $request)
    {
        $adress = CompaniesAddresses::create($request->validated());
        return redirect()->route('companies.show', ['id' =>  $request->companies_id])->with('success', 'Successfully created adress');
    }

    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateAdressRequest $request)
    {
        $adress = CompaniesAddresses::findOrFail($request->id);
        $adress->update($request->validated()); // Use mass assignment with validation
        $adress->save();
        return redirect()->route('companies.show', ['id' =>  $request->companies_id])->with('success', 'Successfully updated adress');
    }
}
