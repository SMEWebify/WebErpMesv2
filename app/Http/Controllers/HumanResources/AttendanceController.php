<?php

namespace App\Http\Controllers\HumanResources;

use App\Http\Controllers\Controller;
use App\Models\HumanResources\Attendance;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'direction' => ['required', 'string', 'in:in,out'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        $lastAttendance = Attendance::where('user_id', $validated['user_id'])
            ->latest('punched_at')
            ->first();

        if ($lastAttendance && $lastAttendance->direction === $validated['direction']) {
            throw ValidationException::withMessages([
                'direction' => ['Cannot punch ' . $validated['direction'] . ' twice in a row.'],
            ]);
        }

        $attendance = Attendance::create([
            'user_id' => $validated['user_id'],
            'punched_at' => now(),
            'direction' => $validated['direction'],
            'source' => $validated['source'] ?? null,
        ]);

        return response()->json($attendance, 201);
    }
}
