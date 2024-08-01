<?php

namespace App\Http\Controllers\Times;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Times\TimesBanckHoliday;
use App\Http\Requests\Times\StoreBanckHolidayRequest;
use App\Http\Requests\Times\UpdateBanckHolidayRequest;

class BanckHolidayController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreBanckHolidayRequest $request)
    {
        $TimesBanckHoliday = TimesBanckHoliday::create($request->only('date', 'label'));
        if($request->fixed) $TimesBanckHoliday->fixed=1;
        else $TimesBanckHoliday->fixed = 2;
        $TimesBanckHoliday->save();

        return redirect()->route('times')->with('success', 'Successfully created Banck Holiday.');
    }

    /**
    * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateBanckHolidayRequest $request)
    {
        $BanckHoliday = TimesBanckHoliday::find($request->id);

        if($request->fixed_update) $BanckHoliday->fixed=1;
        else $BanckHoliday->fixed = 2;

        $BanckHoliday->date=$request->date;
        $BanckHoliday->label=$request->label;
        $BanckHoliday->save();
        return redirect()->route('times')->with('success', 'Successfully updated Banck Holiday.');
    }
}
