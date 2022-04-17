<?php

namespace App\Http\Controllers\Times;

use Illuminate\Http\Request;
use App\Models\Times\TimesAbsence;
use App\Http\Controllers\Controller;
use App\Http\Requests\Times\StoreAbsenceRequest;

class AbsenceController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreAbsenceRequest $request)
    {
        $TimesAbsence = TimesAbsence::create($request->only('user_id', 'absence_type', 'absence_type_day', 'START_DATE', 'end_date'));
        return redirect()->route('times')->with('success', 'Successfully created absence request.');
    }
}
