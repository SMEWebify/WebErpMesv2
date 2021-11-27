<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityCorrection;
use App\Http\Requests\Quality\StoreQualityCorrectionRequest;

class QualityCorrectionController extends Controller
{
    public function store(StoreQualityCorrectionRequest $request)
    {
        $Correction = QualityCorrection::create($request->only('CODE', 'LABEL'));
        return redirect()->route('quality')->with('success', 'Successfully created correction type.');
    }
}
