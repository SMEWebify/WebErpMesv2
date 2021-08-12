<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityFailure;
use App\Http\Requests\Quality\StoreQualityFailureRequest;

class QualityFailureController extends Controller
{
    public function store(StoreQualityFailureRequest $request)
    {
       
        $Faillure = QualityFailure::create($request->only('CODE', 'LABEL'));

        return redirect()->route('quality')->with('success', 'Successfully created faillure type.');

    }
}
