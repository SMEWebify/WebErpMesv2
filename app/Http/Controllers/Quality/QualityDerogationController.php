<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityDerogation;
use App\Http\Requests\Quality\StoreQualityDerogationRequest;
use App\Http\Requests\Quality\UpdateQualityDerogationRequest;

class QualityDerogationController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreQualityDerogationRequest $request)
    {
        $Derogation =  QualityDerogation::create($request->only('code',
                                                                'label', 
                                                                'statu',
                                                                'type', 
                                                                'user_id',
                                                                'pb_descp',  
                                                                'proposal', 
                                                                'reply', 
                                                                'quality_non_conformitie_id',  
                                                                'decision'));
        return redirect()->route('quality')->with('success', 'Successfully created derogation.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdateQualityDerogationRequest $request)
    {
        $QualityDerogation = QualityDerogation::find($request->id);
        $QualityDerogation->label=$request->label;
        $QualityDerogation->statu=$request->statu;
        $QualityDerogation->type=$request->type;
        $QualityDerogation->user_id=$request->user_id;
        $QualityDerogation->pb_descp=$request->pb_descp;
        $QualityDerogation->proposal=$request->proposal;
        $QualityDerogation->reply=$request->reply;
        $QualityDerogation->quality_non_conformitie_id=$request->quality_non_conformitie_id;
        $QualityDerogation->decision=$request->decision;
        $QualityDerogation->save();
        return redirect()->route('quality')->with('success', 'Successfully updated derogation.');
    }
}
