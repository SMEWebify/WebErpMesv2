<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Models\Accounting\AccountingVat;
use App\Http\Requests\Accounting\StoreVatRequest;
use App\Http\Requests\Accounting\UpdateVatRequest;

class VatController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreVatRequest $request)
    {
        $VaT = AccountingVat::create($request->only('code',
                                                    'label',
                                                    'rate'));
        return redirect()->route('accounting')->with('success', 'Successfully created VAT type.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdateVatRequest $request)
    {
        $AccountingVat = AccountingVat::find($request->id);
        $AccountingVat->label=$request->label;
        $AccountingVat->rate=$request->rate;
        $AccountingVat->save();
        return redirect()->route('accounting')->with('success', 'Successfully updated VAT type.');
    }
}
