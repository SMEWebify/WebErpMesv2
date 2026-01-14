<?php

namespace App\Http\Controllers\Inspection;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Inspection\InspectionMeasure;
use App\Models\Inspection\InspectionControlPoint;
use App\Models\Inspection\InspectionMeasureSession;

class InspectionMeasureController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inspection_measure_session_id' => ['required', 'integer'],
            'inspection_control_point_id' => ['required', 'integer'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'measured_value' => ['nullable', 'numeric'],
            'result' => ['nullable', 'in:ok,nok,na'],
            'comment' => ['nullable', 'string'],
            'measured_at' => ['nullable', 'date'],
            'instrument_id' => ['nullable', 'integer'],
        ]);

        $session = InspectionMeasureSession::findOrFail($validated['inspection_measure_session_id']);
        $controlPoint = InspectionControlPoint::findOrFail($validated['inspection_control_point_id']);

        if ($session->status !== 'open') {
            return response()->json(['message' => 'Session is not open.'], 422);
        }

        $project = $controlPoint->Project;
        if ($project && $project->serial_tracking && empty($validated['serial_number'])) {
            return response()->json(['message' => 'Serial number is required for this project.'], 422);
        }

        $resultData = $this->evaluateResult($controlPoint, $validated['measured_value'] ?? null, $validated['result'] ?? null);

        if ($resultData['result'] === 'nok' && empty($validated['comment'])) {
            return response()->json(['message' => 'Comment is required for non-conforming measures.'], 422);
        }

        $measure = InspectionMeasure::create([
            'inspection_measure_session_id' => $session->id,
            'inspection_control_point_id' => $controlPoint->id,
            'serial_number' => $validated['serial_number'] ?? null,
            'measured_value' => $validated['measured_value'] ?? null,
            'result' => $resultData['result'],
            'deviation' => $resultData['deviation'],
            'comment' => $validated['comment'] ?? null,
            'measured_by' => Auth::id(),
            'measured_at' => $validated['measured_at'] ?? now(),
            'instrument_id' => $validated['instrument_id'] ?? null,
        ]);

        return response()->json($measure, 201);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $measure = InspectionMeasure::findOrFail($id);
        $session = $measure->Session;

        if ($session && $session->status !== 'open') {
            return response()->json(['message' => 'Session is not open.'], 422);
        }

        $validated = $request->validate([
            'serial_number' => ['nullable', 'string', 'max:100'],
            'measured_value' => ['nullable', 'numeric'],
            'result' => ['nullable', 'in:ok,nok,na'],
            'comment' => ['nullable', 'string'],
            'measured_at' => ['nullable', 'date'],
            'instrument_id' => ['nullable', 'integer'],
        ]);

        $controlPoint = $measure->ControlPoint;
        $resultData = $this->evaluateResult($controlPoint, $validated['measured_value'] ?? $measure->measured_value, $validated['result'] ?? $measure->result);

        if ($resultData['result'] === 'nok' && empty($validated['comment']) && empty($measure->comment)) {
            return response()->json(['message' => 'Comment is required for non-conforming measures.'], 422);
        }

        $measure->update([
            'serial_number' => $validated['serial_number'] ?? $measure->serial_number,
            'measured_value' => array_key_exists('measured_value', $validated) ? $validated['measured_value'] : $measure->measured_value,
            'result' => $resultData['result'],
            'deviation' => $resultData['deviation'],
            'comment' => $validated['comment'] ?? $measure->comment,
            'measured_at' => $validated['measured_at'] ?? $measure->measured_at,
            'instrument_id' => $validated['instrument_id'] ?? $measure->instrument_id,
        ]);

        return response()->json($measure);
    }

    private function evaluateResult(InspectionControlPoint $controlPoint, ?float $measuredValue, ?string $requestedResult): array
    {
        $hasTolerance = $controlPoint->nominal_value !== null
            && $controlPoint->tol_min !== null
            && $controlPoint->tol_max !== null
            && $measuredValue !== null;

        if ($hasTolerance) {
            $deviation = $measuredValue - $controlPoint->nominal_value;
            $minAllowed = $controlPoint->nominal_value + $controlPoint->tol_min;
            $maxAllowed = $controlPoint->nominal_value + $controlPoint->tol_max;
            $result = ($measuredValue >= $minAllowed && $measuredValue <= $maxAllowed) ? 'ok' : 'nok';

            return [
                'result' => $result,
                'deviation' => $deviation,
            ];
        }

        return [
            'result' => $requestedResult ?? 'ok',
            'deviation' => $measuredValue !== null && $controlPoint->nominal_value !== null
                ? $measuredValue - $controlPoint->nominal_value
                : null,
        ];
    }
}
