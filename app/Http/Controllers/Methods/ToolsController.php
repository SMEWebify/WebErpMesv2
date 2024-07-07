<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsTools;
use App\Http\Requests\Methods\StoreToolRequest;
use App\Http\Requests\Methods\UpdateToolRequest;

class ToolsController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
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

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateToolRequest $request)
    {
        $Tool = MethodsTools::find($request->id);
        $Tool->label=$request->label;
        $Tool->ETAT=$request->ETAT;
        $Tool->cost=$request->cost;
        $Tool->end_date=$request->end_date;
        $Tool->qty=$request->qty;
        $Tool->save();

        return redirect()->route('methods')->with('success', 'Successfully updated tool.');
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
            $Service = MethodsTools::findOrFail($request->id);
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/methods'), $filename);
            $Service->update(['picture' => $filename]);
            $Service->save();
            return redirect()->route('methods')->with('success', 'Successfully updated tool.');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }
}
