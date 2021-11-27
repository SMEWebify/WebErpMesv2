<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Models\Accounting\AccountingVat;
use App\Http\Requests\Accounting\StoreVatRequest;

class VatController extends Controller
{
    //
    public function store(StoreVatRequest $request)
    {
        $VaT = AccountingVat::create($request->only('CODE',
                                                    'LABEL',
                                                    'RATE'));
        return redirect()->route('accounting')->with('success', 'Successfully created VAT type.');
    }
}
