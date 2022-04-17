<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsLocation;
use App\Http\Requests\Methods\StoreLocationRequest;

class LocationsController extends Controller
{
    
     /**
     * @param Request $request
     * @return View
     */
    public function store(StoreLocationRequest $request)
    {
        $Location = MethodsLocation::create($request->only('code', 'label','ressource_id','color'));
        return redirect()->route('methods')->with('success', 'Successfully created location.');
    }
}
