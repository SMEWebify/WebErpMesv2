<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsServices;
use App\Http\Requests\Methods\StoreServicesRequest;
use App\Http\Requests\Methods\UpdateServicesRequest;

class ServicesController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
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

    /**
    * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateServicesRequest $request)
    {
        
        $Service = MethodsServices::find($request->id);
        $Service->ordre=$request->ordre;
        $Service->label=$request->label;
        $Service->type=$request->type;
        $Service->hourly_rate=$request->hourly_rate;
        $Service->margin=$request->margin;
        $Service->color=$request->color;
        $Service->compannie_id=$request->compannie_id;
        $Service->save();

        return redirect()->route('methods')->with('success', 'Successfully updated service.');
    }

    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreImage(Request $request)
    {
        
        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $Service = MethodsServices::findOrFail($request->id);
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/methods'), $filename);
            $Service->update(['picture' => $filename]);
            $Service->save();
            return redirect()->route('methods')->with('success', 'Successfully updated service.');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }
}
