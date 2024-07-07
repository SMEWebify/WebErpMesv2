<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Methods\MethodsStandardNomenclature;
use App\Http\Requests\Methods\StoreStandardNomenclatureRequest;
use App\Http\Requests\Methods\UpdateStandardNomenclatureRequest;

class StandardNomenclatureController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreStandardNomenclatureRequest $request)
    {
        $StandardNomenclature = MethodsStandardNomenclature::create($request->only('code', 'label','comment'));
        return redirect()->route('methods')->with('success', 'Successfully created standard nomenclature.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateStandardNomenclatureRequest $request)
    {
        $StandardNomenclature = MethodsStandardNomenclature::find($request->id);
        $StandardNomenclature->label=$request->label;
        $StandardNomenclature->comment=$request->comment;
        $StandardNomenclature->save();
        return redirect()->route('methods')->with('success', 'Successfully updated standard nomenclature.');
    }
}
