<?php

namespace App\Http\Controllers\Times;

use Illuminate\Http\Request;
use App\Models\Times\TimesAbsence;
use App\Http\Controllers\Controller;
use App\Http\Requests\Times\StoreAbsenceRequest;
use App\Http\Requests\Times\UpdateAbsenceRequest;

class AbsenceController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreAbsenceRequest $request)
    {
        $TimesAbsence = TimesAbsence::create($request->only('user_id', 'absence_type', 'absence_type_day', 'start_date', 'end_date'));
        return redirect()->route('times')->with('success', 'Successfully created absence request.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdateAbsenceRequest $request)
    {
        $Absence = TimesAbsence::find($request->id);
        $Absence->absence_type=$request->absence_type;
        $Absence->absence_type_day=$request->absence_type_day;
        $Absence->start_date=$request->start_date;
        $Absence->end_date=$request->end_date;
        $Absence->save();
        return redirect()->route('times')->with('success', 'Successfully updated Absence Request.');
    }
}
