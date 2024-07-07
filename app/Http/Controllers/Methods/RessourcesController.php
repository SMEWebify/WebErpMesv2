<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsRessources;
use App\Http\Requests\Methods\StoreRessourceRequest;
use App\Http\Requests\Methods\UpdateRessourceRequest;

class RessourcesController extends Controller
{
    
    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRessourceRequest $request)
    {
        $Ressource =  MethodsRessources::create($request->only('ordre','code', 'label','mask_time', 'capacity','section_id', 'color', 'methods_services_id'));
        if($request->hasFile('picture')){
            $Ressource = MethodsRessources::findOrFail($Ressource->id);
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/ressources'), $filename);
            $Ressource->update(['picture' => $filename]);
            $Ressource->save();
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
        return redirect()->route('methods')->with('success', 'Successfully created ressource.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRessourceRequest $request)
    {
        $Ressource = MethodsRessources::find($request->id);
        $Ressource->ordre=$request->ordre;
        $Ressource->label=$request->label;
        $Ressource->mask_time=$request->mask_time;
        $Ressource->capacity=$request->capacity;
        $Ressource->section_id=$request->section_id;
        $Ressource->color=$request->color;
        $Ressource->methods_services_id=$request->methods_services_id;
        $Ressource->save();

        return redirect()->route('methods')->with('success', 'Successfully updated ressource.');
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
            $Service = MethodsRessources::findOrFail($request->id);
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/ressources'), $filename);
            $Service->update(['picture' => $filename]);
            $Service->save();
            return redirect()->route('methods')->with('success', 'Successfully updated ressource.');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }
}
