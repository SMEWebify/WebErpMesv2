<?php

namespace App\Http\Controllers\Times;

use Illuminate\Http\Request;
use App\Models\Times\TimesAbsence;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Times\StoreAbsenceRequest;
use App\Http\Requests\Times\UpdateAbsenceRequest;

class AbsenceController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreAbsenceRequest $request)
    {
        $TimesAbsence = TimesAbsence::create($request->only('user_id', 'absence_type', 'absence_type_day', 'start_date', 'end_date'));

        if($request->user_id == Auth::user()->id){
            return redirect()->route('user.profile', ['id' => Auth::user()->id])->with('success', 'Successfully created absence request.');
        }
        else{
            return redirect()->route('times')->with('success', 'Successfully created absence request.');
        }
    }

    /**
    * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateAbsenceRequest $request)
    {
        $Absence = TimesAbsence::find($request->id);
        $Absence->user_id=$request->user_id;
        $Absence->absence_type=$request->absence_type;
        $Absence->absence_type_day=$request->absence_type_day;
        $Absence->statu=$request->statu;
        $Absence->start_date=$request->start_date;
        $Absence->end_date=$request->end_date;
        $Absence->save();

        if($request->user_id == Auth::user()->id){
            return redirect()->route('user.profile', ['id' => Auth::user()->id])->with('success', 'Successfully updated absence request.');
        }
        else{
            return redirect()->route('times')->with('success', 'Successfully updated absence request.');
        }

    }
}
