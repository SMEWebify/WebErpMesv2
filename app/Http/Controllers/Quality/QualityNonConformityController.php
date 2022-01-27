<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityNonConformity;
use App\Http\Requests\Quality\StoreQualityNonConformityRequest;

class QualityNonConformityController extends Controller
{
    //
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
                                                                'causes_comment',  
                                                                'companie_id'));
        return redirect()->route('quality')->with('success', 'Successfully created non conformitie.');
    }
}
