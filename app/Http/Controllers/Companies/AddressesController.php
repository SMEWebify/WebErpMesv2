<?php

namespace App\Http\Controllers\Companies;

use Illuminate\Http\Request;
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

    public function storeJson(StoreAdressRequest $request)
    {
        $address = CompaniesAddresses::create($request->validated());
        $address->default = $request->boolean('default');
        $address->save();

        if ($address->default) {
            CompaniesAddresses::where('companies_id', $address->companies_id)
                ->where('id', '!=', $address->id)
                ->update(['default' => 0]);
        }

        return response()->json($this->formatAddress($address), 201);
    }

    public function updateJson(UpdateAdressRequest $request, CompaniesAddresses $address)
    {
        $address->update($request->validated());
        $address->default = $request->boolean('default');
        $address->save();

        if ($address->default) {
            CompaniesAddresses::where('companies_id', $address->companies_id)
                ->where('id', '!=', $address->id)
                ->update(['default' => 0]);
        }

        return response()->json($this->formatAddress($address));
    }

    private function formatAddress(CompaniesAddresses $a): array
    {
        return [
            'id'           => $a->id,
            'companies_id' => $a->companies_id,
            'ordre'        => $a->ordre,
            'label'        => $a->label,
            'adress'       => $a->adress,
            'zipcode'      => $a->zipcode,
            'city'         => $a->city,
            'province'     => $a->province,
            'country'      => $a->country,
            'number'       => $a->number,
            'mail'         => $a->mail,
            'default'      => (bool) $a->default,
        ];
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
