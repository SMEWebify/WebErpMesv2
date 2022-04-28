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
        $Service =  MethodsServices::create($request->only('code','ordre', 'label','type', 'hourly_rate','margin', 'color', 'compannie_id'));
        
        if($request->hasFile('picture')){
            $Service = MethodsServices::findOrFail($Service->id);
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/methods'), $filename);
            $Service->update(['picture' => $filename]);
            $Service->save();
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }

        return redirect()->route('methods')->with('success', 'Successfully created service.');
    }
}
