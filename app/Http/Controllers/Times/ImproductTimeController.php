<?php

namespace App\Http\Controllers\Times;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Times\TimesImproductTime;
use App\Http\Requests\Times\StoreImproductTimeRequest;

class ImproductTimeController extends Controller
{
    //
    public function store(StoreImproductTimeRequest $request)
    {
        $TimesImproductTime = TimesImproductTime::create($request->only('label', 'MACHINE_statuS', 'resources_required', 'mask_time'));
        return redirect()->route('times')->with('success', 'Successfully created improduct time type.');
    }
}
