<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsUnits;
use App\Http\Requests\Methods\StoreUnitRequest;

class UnitsController extends Controller
{
    //

    public function store(StoreUnitRequest $request)
    {
       
        $Unit = MethodsUnits::create($request->only('CODE', 'LABEL','TYPE'));

        return redirect()->route('methods')->with('success', 'Successfully created unit.');

    }
}
