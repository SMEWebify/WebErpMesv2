<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityDerogation;
use App\Http\Requests\Quality\StoreQualityDerogationRequest;

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
                                                                'service_id',  
                                                                'pb_descp',  
                                                                'proposal', 
                                                                'reply', 
                                                                'quality_non_conformitie_id',  
                                                                'decision'));
        return redirect()->route('quality')->with('success', 'Successfully created derogation.');
    }
}
