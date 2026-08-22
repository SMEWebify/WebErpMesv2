<?php

namespace App\Http\Controllers\Times;

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
        // Only HR may file a request on behalf of somebody else, otherwise the
        // balance of any colleague could be moved from the profile page.
        $userId = $this->resolveTargetUser($request->input('user_id'));

        $TimesAbsence = TimesAbsence::create([
            'user_id' => $userId,
            'leave_type_id' => $request->input('leave_type_id'),
            'absence_type' => $request->input('absence_type'),
            'absence_type_day' => $request->input('absence_type_day'),
            'hours_count' => $request->input('hours_count'),
            'comment' => $request->input('comment'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ]);

        if($TimesAbsence->user_id == Auth::user()->id){
            return redirect()->route('user.profile', ['id' => Auth::user()->id])->with('success', __('general_content.absence_request_created_success_trans_key'));
        }
        else{
            return redirect()->route('times')->with('success', __('general_content.absence_request_created_success_trans_key'));
        }
    }

    /**
    * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateAbsenceRequest $request)
    {
        $Absence = TimesAbsence::findOrFail($request->id);

        $isHr = Auth::user()?->can('human-resources-menu') ?? false;

        // An employee may amend their own pending request; approving it, or
        // touching somebody else's, stays an HR action.
        abort_unless($isHr || (int) $Absence->user_id === (int) Auth::id(), 403);
        abort_unless($isHr || (int) $Absence->statu === 1, 403);

        if ($isHr) {
            $Absence->user_id = $this->resolveTargetUser($request->input('user_id'));
            $Absence->statu = $request->input('statu', $Absence->statu);
        }

        $Absence->leave_type_id = $request->input('leave_type_id');
        $Absence->absence_type = $request->input('absence_type');
        $Absence->absence_type_day = $request->input('absence_type_day');
        $Absence->hours_count = $request->input('hours_count');
        $Absence->comment = $request->input('comment');
        $Absence->start_date = $request->input('start_date');
        $Absence->end_date = $request->input('end_date');
        $Absence->save();

        if($Absence->user_id == Auth::user()->id){
            return redirect()->route('user.profile', ['id' => Auth::user()->id])->with('success', __('general_content.absence_request_updated_success_trans_key'));
        }
        else{
            return redirect()->route('times')->with('success', __('general_content.absence_request_updated_success_trans_key'));
        }

    }

    /**
     * The employee an absence is filed for: the requested one for HR, the
     * signed-in user otherwise.
     */
    private function resolveTargetUser(mixed $requested): int
    {
        $isHr = Auth::user()?->can('human-resources-menu') ?? false;

        return $isHr && $requested !== null ? (int) $requested : (int) Auth::id();
    }
}
