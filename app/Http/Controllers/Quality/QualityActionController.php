<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityAction;
use App\Http\Requests\Quality\StoreQualityActionRequest;
use App\Http\Requests\Quality\UpdateQualityActionRequest;

class QualityActionController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreQualityActionRequest $request)
    {
        $Action = QualityAction::create($request->only('code',
                                                        'label', 
                                                        'statu',
                                                        'type', 
                                                        'user_id',
                                                        'pb_descp',  
                                                        'cause',  
                                                        'action', 
                                                        'color', 
                                                        'quality_non_conformitie_id'));
        return redirect()->route('quality')->with('success', 'Successfully created action.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateQualityActionRequest $request)
    {
        $QualityAction = QualityAction::find($request->id);
        $QualityAction->label=$request->label;
        $QualityAction->statu=$request->statu;
        $QualityAction->type=$request->type;
        $QualityAction->user_id=$request->user_id;
        $QualityAction->pb_descp=$request->pb_descp;
        $QualityAction->cause=$request->cause;
        $QualityAction->action=$request->action;
        $QualityAction->color=$request->color;
        $QualityAction->quality_non_conformitie_id=$request->quality_non_conformitie_id;
        $QualityAction->save();
        return redirect()->route('quality')->with('success', 'Successfully updated action.');
    }
}
