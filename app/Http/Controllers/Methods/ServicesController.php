<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsServices;
use App\Http\Requests\Methods\StoreServicesRequest;

class ServicesController extends Controller
{
    //
    public function store(StoreServicesRequest $request)
    {
       
        $Service =  MethodsServices::create($request->only('CODE','ORDRE', 'LABEL','TYPE', 'HOURLY_RATE','MARGIN', 'COLOR','PICTURE', 'compannie_id'));

        if($request->hasFile('PICTURE')){
            $path = $request->PICTURE->store('images/methods','public');
            $Service->update(['PICTURE' => $path]);
        }

        return redirect()->route('methods')->with('success', 'Successfully created service.');

    }
}
