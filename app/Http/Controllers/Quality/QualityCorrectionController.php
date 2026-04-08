<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityCorrection;
use App\Http\Requests\Quality\UpdateQualityCorrectionRequest;
use App\Http\Requests\Quality\StoreQualityCorrectionRequest;

class QualityCorrectionController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreQualityCorrectionRequest $request)
    {
        $Correction = QualityCorrection::create($request->only('code', 'label'));
        return redirect()->route('quality')->with('success', 'Successfully created correction type.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateQualityCorrectionRequest $request)
    {
        $QualityCorrection = QualityCorrection::find($request->id);
        $QualityCorrection->label=$request->label;
        $QualityCorrection->save();
        return redirect()->route('quality')->with('success', 'Successfully updated correction type.');
    }

    /**
     * JSON: create a correction type.
     */
    public function jsonStore(Request $request)
    {
        $request->validate(['label' => 'required|string|max:255']);
        $item = QualityCorrection::create($request->only('code', 'label'));
        return response()->json(['item' => ['id' => $item->id, 'code' => $item->code, 'label' => $item->label]], 201);
    }

    /**
     * JSON: update a correction type.
     */
    public function jsonUpdate(Request $request, $id)
    {
        $request->validate(['label' => 'required|string|max:255']);
        $item = QualityCorrection::findOrFail($id);
        $item->label = $request->label;
        $item->save();
        return response()->json(['item' => ['id' => $item->id, 'code' => $item->code, 'label' => $item->label]]);
    }
}
