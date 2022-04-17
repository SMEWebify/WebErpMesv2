<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsServices;
use App\Http\Requests\Methods\StoreServicesRequest;

class ServicesController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreServicesRequest $request)
    {
        $Service =  MethodsServices::create($request->only('code','ordre', 'label','type', 'hourly_rate','margin', 'color','picture', 'compannie_id'));
        if($request->hasFile('picture')){
            $path = $request->picture->store('images/methods','public');
            $Service->update(['picture' => $path]);
        }
        return redirect()->route('methods')->with('success', 'Successfully created service.');
    }
}
