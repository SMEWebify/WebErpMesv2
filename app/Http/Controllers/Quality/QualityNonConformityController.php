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
       
        $NonConformity =  QualityNonConformity::create($request->only('CODE',
                                                                'LABEL', 
                                                                'STATU',
                                                                'TYPE', 
                                                                'user_id',
                                                                'service_id',  
                                                                'failure_id',  
                                                                'failure_COMMENT', 
                                                                'causes_id', 
                                                                'causes_COMMENT',  
                                                                'correction_id',  
                                                                'correction_COMMENT', 
                                                                'causes_COMMENT',  
                                                                'companie_id'));

        return redirect()->route('quality')->with('success', 'Successfully created non conformitie.');

    }
}
