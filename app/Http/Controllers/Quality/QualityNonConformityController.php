<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityNonConformity;
use App\Http\Requests\Quality\StoreQualityNonConformityRequest;
use App\Http\Requests\Quality\UpdateQualityNonConformityRequest;

class QualityNonConformityController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreQualityNonConformityRequest $request)
    {
        $NonConformity =  QualityNonConformity::create($request->only('code',
                                                                'label', 
                                                                'statu',
                                                                'type', 
                                                                'user_id',
                                                                'service_id',  
                                                                'failure_id',  
                                                                'failure_comment', 
                                                                'causes_id', 
                                                                'causes_comment',  
                                                                'correction_id',  
                                                                'correction_comment',   
                                                                'companie_id'));
        return redirect()->route('quality')->with('success', 'Successfully created non conformitie.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdateQualityNonConformityRequest $request)
    {
        $QualityNonConformity = QualityNonConformity::find($request->id);
        $QualityNonConformity->label=$request->label;
        $QualityNonConformity->statu=$request->statu;
        $QualityNonConformity->type=$request->type;
        $QualityNonConformity->user_id=$request->user_id;
        $QualityNonConformity->service_id=$request->service_id;
        $QualityNonConformity->failure_id=$request->failure_id;
        $QualityNonConformity->failure_comment=$request->failure_comment;
        $QualityNonConformity->causes_id=$request->causes_id;
        $QualityNonConformity->causes_comment=$request->causes_comment;
        $QualityNonConformity->correction_id=$request->correction_id;
        $QualityNonConformity->correction_comment=$request->correction_comment;
        $QualityNonConformity->companie_id=$request->companie_id;
        $QualityNonConformity->save();
        return redirect()->route('quality')->with('success', 'Successfully updated non conformitie.');
    }
}
