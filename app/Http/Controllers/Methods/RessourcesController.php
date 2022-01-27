<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsRessources;
use App\Http\Requests\Methods\StoreRessourceRequest;

class RessourcesController extends Controller
{
    //
    public function store(StoreRessourceRequest $request)
    {
        $Ressource =  MethodsRessources::create($request->only('ordre','code', 'label','mask_time', 'capacity','section_id', 'color', 'service_id','color'));
        if($request->hasFile('picture')){
            $path = $request->picture->store('images/methods','public');
            $Ressource->update(['picture' => $path]);
        }
        return redirect()->route('methods')->with('success', 'Successfully created ressource.');
    }
}
