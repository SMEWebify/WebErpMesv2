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
     * @param Request $request
     * @return View
     */
    public function store(StoreBanckHolidayRequest $request)
    {
        $TimesBanckHoliday = TimesBanckHoliday::create($request->only('fixed', 'date', 'label'));
        return redirect()->route('times')->with('success', 'Successfully created Banck Holiday.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdateBanckHolidayRequest $request)
    {
        $BanckHoliday = TimesBanckHoliday::find($request->id);
        $BanckHoliday->fixed=$request->fixed;
        $BanckHoliday->date=$request->date;
        $BanckHoliday->label=$request->label;
        $BanckHoliday->save();
        return redirect()->route('times')->with('success', 'Successfully updated Banck Holiday.');
    }
}
