<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Models\Methods\MethodsFamilies;
use App\Http\Requests\Methods\StoreFamilyRequest;

class FamiliesController extends Controller
{
    //
    public function store(StoreFamilyRequest $request)
    {
        $Family = MethodsFamilies::create($request->only('code', 'label','service_id'));
        return redirect()->route('methods')->with('success', 'Successfully created family.');
    }
}
