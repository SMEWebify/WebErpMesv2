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
        $Ressource =  MethodsRessources::create($request->only('ORDRE','CODE', 'LABEL','MASK_TIME', 'CAPACITY','section_id', 'COLOR', 'service_id','COLOR'));
        if($request->hasFile('PICTURE')){
            $path = $request->PICTURE->store('images/methods','public');
            $Ressource->update(['PICTURE' => $path]);
        }
        return redirect()->route('methods')->with('success', 'Successfully created ressource.');
    }
}
