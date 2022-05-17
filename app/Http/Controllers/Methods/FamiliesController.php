<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsFamilies;
use App\Http\Requests\Methods\StoreFamilyRequest;
use App\Http\Requests\Methods\UpdateFamilyRequest;

class FamiliesController extends Controller
{
    
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreFamilyRequest $request)
    {
        $Family = MethodsFamilies::create($request->only('code', 'label','service_id'));
        return redirect()->route('methods')->with('success', 'Successfully created family.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdateFamilyRequest $request)
    {
        $Familie = MethodsFamilies::find($request->id);
        $Familie->label=$request->label;
        $Familie->service_id=$request->service_id;
        $Familie->save();
        return redirect()->route('methods')->with('success', 'Successfully updated Family.');
    }
}
