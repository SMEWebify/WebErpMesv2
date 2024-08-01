<?php

namespace App\Http\Controllers\Times;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Times\TimesImproductTime;
use App\Http\Requests\Times\StoreImproductTimeRequest;
use App\Http\Requests\Times\UpdateImproductTimeRequest;

class ImproductTimeController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreImproductTimeRequest $request)
    {
        $TimesImproductTime = TimesImproductTime::create($request->only('label', 'times_machine_events_id'));
        if($request->resources_required) $TimesImproductTime->resources_required=1;
        else $TimesImproductTime->resources_required = 2;
        if($request->mask_time) $TimesImproductTime->mask_time=1;
        else $TimesImproductTime->mask_time = 2;
        $TimesImproductTime->save();

        return redirect()->route('times')->with('success', 'Successfully created improduct time type.');
    }

    /**
    * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateImproductTimeRequest $request)
    {
        $ImproductTime = TimesImproductTime::find($request->id);
        $ImproductTime->label=$request->label;
        $ImproductTime->times_machine_events_id=$request->times_machine_events_id;

        if($request->resources_required_update) $ImproductTime->resources_required=1;
        else $ImproductTime->resources_required = 2;
        if($request->mask_time_non_product_update) $ImproductTime->mask_time=1;
        else $ImproductTime->mask_time = 2;

        $ImproductTime->save();
        return redirect()->route('times')->with('success', 'Successfully updated Improduct Time.');
    }
}
