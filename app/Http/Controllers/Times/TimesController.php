<?php

namespace App\Http\Controllers\Times;

use App\Models\Times\TimesAbsence;
use App\Models\HumanResources\LeaveType;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Times\TimesBanckHoliday;
use App\Models\Times\TimesMachineEvent;
use App\Models\Times\TimesImproductTime;

class TimesController extends Controller
{
    protected $SelectDataService;
    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }
    
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $TimesAbsences = TimesAbsence::with(['User', 'leaveType'])->orderByDesc('start_date')->get();
        $LeaveTypes = LeaveType::active()->orderBy('ordre')->orderBy('label')->get();
        $TimesBanckHolidays = TimesBanckHoliday::All();
        $TimesImproductTimes = TimesImproductTime::All();
        $TimesMachineEvents = TimesMachineEvent::All();
        $TimesMachineEventsSelect = TimesMachineEvent::select('id', 'label')->orderBy('label')->get();
        $user = Auth::user();
        $userSelect = $this->SelectDataService->getUsers();
        
        return view('times/times-index',[
            'TimesAbsences' => $TimesAbsences,
            'LeaveTypes' => $LeaveTypes,
            'TimesBanckHolidays' => $TimesBanckHolidays,
            'TimesImproductTimes' => $TimesImproductTimes,
            'TimesMachineEvents' => $TimesMachineEvents,
            'TimesMachineEventsSelect' => $TimesMachineEventsSelect,
            'user' => $user,
            'userSelect' => $userSelect,
        ]);
    }
}
