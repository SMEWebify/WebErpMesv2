<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityDerogation;
use App\Http\Requests\Quality\StoreQualityDerogationRequest;

class QualityDerogationController extends Controller
{
    //
    public function store(StoreQualityDerogationRequest $request)
    {
        $Derogation =  QualityDerogation::create($request->only('CODE',
                                                                'LABEL', 
                                                                'statu',
                                                                'TYPE', 
                                                                'user_id',
                                                                'service_id',  
                                                                'PB_DESCP',  
                                                                'PROPOSAL', 
                                                                'REPLY', 
                                                                'quality_non_conformitie_id',  
                                                                'DECISION'));
        return redirect()->route('quality')->with('success', 'Successfully created derogation.');
    }
}
