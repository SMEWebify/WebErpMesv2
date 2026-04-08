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

    /**
     * JSON: create a failure type.
     */
    public function jsonStore(Request $request)
    {
        $request->validate(['label' => 'required|string|max:255']);
        $item = QualityFailure::create($request->only('code', 'label'));
        return response()->json(['item' => ['id' => $item->id, 'code' => $item->code, 'label' => $item->label]], 201);
    }

    /**
     * JSON: update a failure type.
     */
    public function jsonUpdate(Request $request, $id)
    {
        $request->validate(['label' => 'required|string|max:255']);
        $item = QualityFailure::findOrFail($id);
        $item->label = $request->label;
        $item->save();
        return response()->json(['item' => ['id' => $item->id, 'code' => $item->code, 'label' => $item->label]]);
    }
}
