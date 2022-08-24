<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityCause;
use App\Http\Requests\Quality\StoreQualityCauseRequest;
use App\Http\Requests\Quality\UpdateQualityCauseRequest;

class QualityCauseController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreQualityCauseRequest $request)
    {
        $Cause = QualityCause::create($request->only('code', 'label'));
        return redirect()->route('quality')->with('success', 'Successfully created cause type.');
    }

        /**
     * @param $request
     * @return View
     */
    public function update(UpdateQualityCauseRequest $request)
    {
        $QualityCause = QualityCause::find($request->id);
        $QualityCause->label=$request->label;
        $QualityCause->save();
        return redirect()->route('quality')->with('success', 'Successfully updated cause type.');
    }
}
