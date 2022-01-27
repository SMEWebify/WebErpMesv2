<?php

namespace App\Http\Controllers\Times;

use Illuminate\Http\Request;
use App\Models\Times\TimesAbsence;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Times\TimesBanckHoliday;
use App\Models\Times\TimesMachineEvent;
use App\Models\Times\TimesImproductTime;

class TimesController extends Controller
{
    //
    public function index()
    {
        $TimesAbsences = TimesAbsence::All();
        $TimesBanckHolidays = TimesBanckHoliday::All();
        $TimesImproductTimes = TimesImproductTime::All();
        $TimesMachineEvents = TimesMachineEvent::All();
        $TimesMachineEventsSelect = TimesMachineEvent::select('id', 'label')->orderBy('label')->get();
        $user = Auth::user();
        
        return view('times/times-index',[
            'TimesAbsences' => $TimesAbsences,
            'TimesBanckHolidays' => $TimesBanckHolidays,
            'TimesImproductTimes' => $TimesImproductTimes,
            'TimesMachineEvents' => $TimesMachineEvents,
            'TimesMachineEventsSelect' => $TimesMachineEventsSelect,
            'user' => $user 
        ]);
    }
}
