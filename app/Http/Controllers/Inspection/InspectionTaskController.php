<?php

namespace App\Http\Controllers\Inspection;

use App\Http\Controllers\Controller;
use App\Models\Inspection\InspectionControlPoint;
use App\Models\Planning\Task;
use App\Services\Inspection\TaskInspection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Onglet « Contrôle » de l'écran tâche : l'opérateur mesure les cotes du plan
 * de contrôle validé de l'article. Ouvert à tout opérateur, comme l'écran tâche.
 */
class InspectionTaskController extends Controller
{
    public function __construct(private readonly TaskInspection $inspection)
    {
    }

    public function show(int $id): JsonResponse
    {
        $sheet = $this->inspection->sheet($this->task($id));
        abort_if($sheet === null, 404, __('inspection.no_plan_for_task'));

        return response()->json($sheet + ['trans' => InspectionPlanController::trans()]);
    }

    public function measure(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'point_id'  => ['required', 'integer', 'exists:inspection_control_points,id'],
            'part'      => ['required', 'integer', 'min:1', 'max:' . TaskInspection::MAX_PARTS],
            'value'     => ['nullable', 'numeric'],
            'result'    => ['nullable', 'in:ok,nok'],
            'device_id' => ['nullable', 'integer', 'exists:quality_control_devices,id'],
            'comment'   => ['nullable', 'string', 'max:1000'],
        ]);

        abort_if(! array_key_exists('value', $data) && empty($data['result']), 422, __('inspection.measure_empty'));

        $task = $this->task($id);
        $measure = $this->inspection->record(
            $task,
            InspectionControlPoint::findOrFail($data['point_id']),
            (int) $data['part'],
            isset($data['value']) ? (float) $data['value'] : null,
            $data['result'] ?? null,
            $data['device_id'] ?? null,
            $data['comment'] ?? null,
        );

        return response()->json([
            'measure' => [
                'id'     => $measure->id,
                'value'  => $measure->measured_value,
                'result' => $measure->result,
                'by'     => $request->user()?->name,
                'at'     => $measure->measured_at?->toIso8601String(),
            ],
            'summary' => $this->inspection->summary($task),
        ]);
    }

    public function close(int $id): JsonResponse
    {
        $task = $this->task($id);
        $this->inspection->close($task);

        return response()->json(['summary' => $this->inspection->summary($task)]);
    }

    private function task(int $id): Task
    {
        return Task::with('OrderLines.Product', 'OrderLines.order')->findOrFail($id);
    }
}
