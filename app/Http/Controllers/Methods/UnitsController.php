<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsUnits;
use App\Http\Requests\Methods\StoreUnitRequest;
use App\Http\Requests\Methods\UpdateUnitRequest;

class UnitsController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreUnitRequest $request)
    {
        $Unit = MethodsUnits::create($request->only('code', 'label','type'));
        return redirect()->route('methods')->with('success', 'Successfully created unit.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdateUnitRequest $request)
    {
        $Unit = MethodsUnits::find($request->id);
        $Unit->label=$request->label;
        $Unit->type=$request->type;
        $Unit->save();
        return redirect()->route('methods')->with('success', 'Successfully updated Unit.');
    }
}
