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
        $Service =  MethodsTools::create($request->only('code','label', 'ETAT','cost', 'end_date','comment', 'qty'));
        if($request->hasFile('picture')){
            $path = $request->picture->store('images/methods','public');
            $Service->update(['picture' => $path]);
        }
        return redirect()->route('methods')->with('success', 'Successfully created tool.');
    }
}
