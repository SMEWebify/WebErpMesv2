<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Http\Requests\Quality\StoreQualityCauseRequest;
use App\Models\Quality\QualityCause;

class QualityCauseController extends Controller
{
    public function store(StoreQualityCauseRequest $request)
    {
        $Cause = QualityCause::create($request->only('CODE', 'LABEL'));
        return redirect()->route('quality')->with('success', 'Successfully created cause type.');
    }
}
