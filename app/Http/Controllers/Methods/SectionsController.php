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
        $Section = MethodsSection::create($request->only('ordre','code', 'label','user_id','color'));
        return redirect()->route('methods')->with('success', 'Successfully created section.');
    }
}
