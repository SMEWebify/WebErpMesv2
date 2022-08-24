<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityCorrection;
use App\Http\Requests\Quality\UpdateQualityCorrectionRequest;
use App\Http\Requests\Quality\StoreQualityCorrectionRequest;

class QualityCorrectionController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreQualityCorrectionRequest $request)
    {
        $Correction = QualityCorrection::create($request->only('code', 'label'));
        return redirect()->route('quality')->with('success', 'Successfully created correction type.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdateQualityCorrectionRequest $request)
    {
        $QualityCorrection = QualityCorrection::find($request->id);
        $QualityCorrection->label=$request->label;
        $QualityCorrection->save();
        return redirect()->route('quality')->with('success', 'Successfully updated correction type.');
    }
}
