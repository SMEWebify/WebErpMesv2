<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsFamilies;
use App\Http\Requests\Methods\StoreFamilyRequest;
use App\Http\Requests\Methods\UpdateFamilyRequest;

class FamiliesController extends Controller
{
    
    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreFamilyRequest $request)
    {
        $Family = MethodsFamilies::create($request->only('code', 'label','methods_services_id'));
        return redirect()->route('methods')->with('success', 'Successfully created family.');
    }

    /**
    * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateFamilyRequest $request)
    {
        $Familie = MethodsFamilies::find($request->id);
        $Familie->label=$request->label;
        $Familie->methods_services_id=$request->methods_services_id;
        $Familie->save();
        return redirect()->route('methods')->with('success', 'Successfully updated Family.');
    }
}
