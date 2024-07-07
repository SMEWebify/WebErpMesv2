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
        $TimesImproductTime = TimesImproductTime::create($request->only('label', 'times_machine_events_id', 'resources_required', 'mask_time'));
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
        $ImproductTime->resources_required=$request->resources_required;
        $ImproductTime->mask_time=$request->mask_time;
        $ImproductTime->save();
        return redirect()->route('times')->with('success', 'Successfully updated Improduct Time.');
    }
}
