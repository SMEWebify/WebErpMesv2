<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityFailure;
use App\Http\Requests\Quality\StoreQualityFailureRequest;
use App\Http\Requests\Quality\UpdateQualityFailureRequest;

class QualityFailureController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreQualityFailureRequest $request)
    {
        $Faillure = QualityFailure::create($request->only('code', 'label'));
        return redirect()->route('quality')->with('success', 'Successfully created faillure type.');
    }

    /**
    * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateQualityFailureRequest $request)
    {
        $QualityFailure = QualityFailure::find($request->id);
        $QualityFailure->label=$request->label;
        $QualityFailure->save();
        return redirect()->route('quality')->with('success', 'Successfully updated faillure type.');
    }
}
