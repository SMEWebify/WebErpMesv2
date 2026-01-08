<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Services\SelectDataService;

class AttendanceController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * Display the public attendance form.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $userSelect = $this->SelectDataService->getUsers();

        return view('attendance.index', [
            'userSelect' => $userSelect,
        ]);
    }

    /**
     * Store a new attendance entry.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'direction' => ['required', 'in:in,out'],
        ]);

        Attendance::create([
            'user_id' => $validated['user_id'],
            'punched_at' => now(),
            'direction' => $validated['direction'],
            'source' => 'public',
        ]);

        return redirect()
            ->route('attendance.index')
            ->with('status', __('general_content.attendance_success_trans_key'));
    }
}
