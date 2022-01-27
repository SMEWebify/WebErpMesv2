<?php

namespace App\Http\Controllers\Times;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Times\TimesBanckHoliday;
use App\Http\Requests\Times\StoreBanckHolidayRequest;

class BanckHolidayController extends Controller
{
    //
    public function store(StoreBanckHolidayRequest $request)
    {
        $TimesBanckHoliday = TimesBanckHoliday::create($request->only('fixed', 'date', 'label'));
        return redirect()->route('times')->with('success', 'Successfully created Banck Holiday.');
    }
}
