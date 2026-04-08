<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityCause;
use App\Http\Requests\Quality\StoreQualityCauseRequest;
use App\Http\Requests\Quality\UpdateQualityCauseRequest;

class QualityCauseController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreQualityCauseRequest $request)
    {
        $Cause = QualityCause::create($request->only('code', 'label'));
        return redirect()->route('quality')->with('success', 'Successfully created cause type.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateQualityCauseRequest $request)
    {
        $QualityCause = QualityCause::find($request->id);
        $QualityCause->label=$request->label;
        $QualityCause->save();
        return redirect()->route('quality')->with('success', 'Successfully updated cause type.');
    }

    /**
     * JSON: create a cause type.
     */
    public function jsonStore(Request $request)
    {
        $request->validate(['label' => 'required|string|max:255']);
        $item = QualityCause::create($request->only('code', 'label'));
        return response()->json(['item' => ['id' => $item->id, 'code' => $item->code, 'label' => $item->label]], 201);
    }

    /**
     * JSON: update a cause type.
     */
    public function jsonUpdate(Request $request, $id)
    {
        $request->validate(['label' => 'required|string|max:255']);
        $item = QualityCause::findOrFail($id);
        $item->label = $request->label;
        $item->save();
        return response()->json(['item' => ['id' => $item->id, 'code' => $item->code, 'label' => $item->label]]);
    }
}
