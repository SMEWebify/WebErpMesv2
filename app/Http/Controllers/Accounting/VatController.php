<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Models\Accounting\AccountingVat;
use App\Http\Requests\Accounting\StoreVatRequest;
use App\Http\Requests\Accounting\UpdateVatRequest;

class VatController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function store(StoreVatRequest $request)
    {
        $vat = AccountingVat::create($request->validated());
        return redirect()->route('accounting')->with('success', 'Successfully created VAT type.');
    }

    /**
     * @param $request
     * @return \Illuminate\Contracts\View\View
     */
    public function update(UpdateVatRequest $request)
    { 
        $vat = AccountingVat::findOrFail($request->id);

        $vat->update($request->validated());

        // Set other VATs to non-default if this one is marked default
        if ($request->default) {
            AccountingVat::where('id', '!=', $vat->id)->update(['default' => 0]);
        }
        return redirect()->route('accounting')->with('success', 'Successfully updated VAT type.');
    }
}
