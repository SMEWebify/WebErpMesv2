<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsLocation;
use App\Http\Requests\Methods\StoreLocationRequest;
use App\Http\Requests\Methods\UpdateLocationRequest;

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

    /**
     * @param $request
     * @return View
     */
    public function update(UpdateLocationRequest $request)
    {
        $Location = MethodsLocation::find($request->id);
        $Location->label=$request->label;
        $Location->color=$request->color;
        $Location->ressource_id=$request->ressource_id;
        $Location->save();
        return redirect()->route('methods')->with('success', 'Successfully updated Location.');
    }
}
