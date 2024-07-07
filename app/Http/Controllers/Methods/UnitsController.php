<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsUnits;
use App\Http\Requests\Methods\StoreUnitRequest;
use App\Http\Requests\Methods\UpdateUnitRequest;

class UnitsController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreUnitRequest $request)
    {
        $Unit = MethodsUnits::create($request->only('code', 'label','type'));
        return redirect()->route('methods')->with('success', 'Successfully created unit.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateUnitRequest $request)
    {
        if($request->default == 1) MethodsUnits::query()->update(['default' => 0]);
        $Unit = MethodsUnits::find($request->id);
        $Unit->label=$request->label;
        $Unit->type=$request->type;
        $Unit->default=$request->default;
        $Unit->save();
        return redirect()->route('methods')->with('success', 'Successfully updated Unit.');
    }
}
