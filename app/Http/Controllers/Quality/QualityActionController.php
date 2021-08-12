<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use App\Models\Quality\QualityAction;
use App\Http\Requests\Quality\StoreQualityActionRequest;

class QualityActionController extends Controller
{
    public function store(StoreQualityActionRequest $request)
    {
       
        $Action = QualityAction::create($request->only('CODE', 'LABEL','TYPE', 'ETAT'));

        return redirect()->route('quality')->with('success', 'Successfully created action.');

    }
}
