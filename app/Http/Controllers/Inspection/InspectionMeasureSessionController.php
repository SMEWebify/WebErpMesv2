<?php

namespace App\Http\Controllers\Inspection;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Inspection\InspectionMeasureSession;
use App\Models\Inspection\InspectionProject;

class InspectionMeasureSessionController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $session = InspectionMeasureSession::with('Measures')->findOrFail($id);

        return response()->json($session);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $projectId)
    {
        $project = InspectionProject::findOrFail($projectId);

        $validated = $request->validate([
            'type' => ['nullable', 'in:lot,serial,recheck'],
            'quantity_to_measure' => ['nullable', 'integer', 'min:0'],
            'started_at' => ['nullable', 'date'],
        ]);

        $sessionCode = strtoupper(Str::random(8));

        $session = InspectionMeasureSession::create([
            'inspection_project_id' => $project->id,
            'session_code' => $sessionCode,
            'type' => $validated['type'] ?? 'lot',
            'quantity_to_measure' => $validated['quantity_to_measure'] ?? null,
            'started_at' => $validated['started_at'] ?? now(),
            'status' => 'open',
            'created_by' => Auth::id(),
        ]);

        return response()->json($session, 201);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit($id)
    {
        $session = InspectionMeasureSession::findOrFail($id);
        $session->status = 'submitted';
        $session->ended_at = now();
        $session->save();

        return response()->json($session);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function close($id)
    {
        $session = InspectionMeasureSession::findOrFail($id);
        $session->status = 'closed';
        $session->ended_at = now();
        $session->save();

        return response()->json($session);
    }
}
