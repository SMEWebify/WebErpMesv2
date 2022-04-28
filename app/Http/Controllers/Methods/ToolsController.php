<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsTools;
use App\Http\Requests\Methods\StoreToolRequest;

class ToolsController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreToolRequest $request)
    {
        $Tool =  MethodsTools::create($request->only('code','label', 'ETAT','cost', 'end_date','comment', 'qty'));

        if($request->hasFile('picture')){
            $Tool = MethodsTools::findOrFail($Tool->id);
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/tools'), $filename);
            $Tool->update(['picture' => $filename]);
            $Tool->save();
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }

        return redirect()->route('methods')->with('success', 'Successfully created tool.');
    }
}
