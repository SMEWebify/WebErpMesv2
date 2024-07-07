<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsSection;
use App\Http\Requests\Methods\StoreSectionRequest;
use App\Http\Requests\Methods\UpdateSectiontRequest;

class SectionsController extends Controller
{
    
    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreSectionRequest $request)
    {
        $Section = MethodsSection::create($request->only('ordre','code', 'label','user_id','color'));
        return redirect()->route('methods')->with('success', 'Successfully created section.');
    }

    /**
    * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateSectiontRequest $request)
    {
        $Section = MethodsSection::find($request->id);
        $Section->ordre=$request->ordre;
        $Section->label=$request->label;
        $Section->color=$request->color;
        $Section->user_id=$request->user_id;
        $Section->save();
        return redirect()->route('methods')->with('success', 'Successfully updated section.');
    }
}
