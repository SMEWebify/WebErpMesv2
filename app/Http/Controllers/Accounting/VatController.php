<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Models\Accounting\AccountingVat;
use App\Http\Requests\Accounting\StoreVatRequest;

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
}
