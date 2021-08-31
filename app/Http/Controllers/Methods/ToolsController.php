<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsTools;
use App\Http\Requests\Methods\StoreToolRequest;

class ToolsController extends Controller
{
    //
    public function store(StoreToolRequest $request)
    {
       
        $Service =  MethodsTools::create($request->only('CODE','LABEL', 'ETAT','COST', 'END_DATE','COMMENT', 'QTY'));

        if($request->hasFile('PICTURE')){
            $path = $request->PICTURE->store('images/methods','public');
            $Service->update(['PICTURE' => $path]);
        }

        return redirect()->route('methods')->with('success', 'Successfully created tool.');

    }
}
