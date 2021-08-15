<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsSection;
use App\Http\Requests\Methods\StoreSectionRequest;

class SectionsController extends Controller
{
    //

    public function store(StoreSectionRequest $request)
    {
       
        $Section = MethodsSection::create($request->only('ORDRE','CODE', 'LABEL','user_id','COLOR'));

        return redirect()->route('methods')->with('success', 'Successfully created section.');

    }
}
