<?php

namespace App\Http\Controllers\Inspection;

use App\Http\Controllers\Controller;
use App\Models\Inspection\InspectionControlPoint;
use App\Models\Inspection\InspectionMeasure;
use App\Models\Inspection\InspectionProject;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Services\Inspection\TaskInspection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Saisie des mesures d'un dossier de contrôle, par deux portes :
 * - l'onglet Contrôle de l'écran tâche (/production/Task/Statu/Api/{id}/inspection…) ;
 * - le contrôle libre d'un article choisi à la main (/inspection-dossiers/{dossier}…).
 * Les deux renvoient la même fiche et acceptent les mêmes mesures. Ouvert à
 * tout opérateur, comme l'écran tâche.
 */
class InspectionDossierController extends Controller
{
    public function __construct(private readonly TaskInspection $inspection)
    {
    }

    // ------------------------------------------------------------------
    // Contrôle libre
    // ------------------------------------------------------------------

    public function page(InspectionProject $dossier)
    {
        abort_if($dossier->inspection_plan_id === null, 404);

        return view('quality/quality-inspection', [
            'dossier' => $dossier,
            'baseUrl' => route('inspection.dossiers.show', ['dossier' => $dossier->id]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'products_id'  => ['required', 'integer', 'exists:products,id'],
            'qty'          => ['required', 'integer', 'min:1', 'max:100000'],
            'revision'     => ['nullable', 'string', 'max:50'],
            'title'        => ['nullable', 'string', 'max:255'],
            'companies_id' => ['nullable', 'integer', 'exists:companies,id'],
            'is_fai'       => ['nullable', 'boolean'],
        ]);

        $dossier = $this->inspection->createFree(
            Products::findOrFail($data['products_id']),
            (int) $data['qty'],
            $data['revision'] ?? null,
            $data['title'] ?? null,
            $data['companies_id'] ?? null,
            (bool) ($data['is_fai'] ?? false),
        );

        return response()->json(['url' => route('quality.inspections.show', ['dossier' => $dossier->id])], 201);
    }

    public function show(InspectionProject $dossier): JsonResponse
    {
        abort_if($dossier->inspection_plan_id === null, 404);

        return response()->json($this->inspection->sheetOf($dossier) + ['trans' => InspectionPlanController::trans()]);
    }

    public function measure(Request $request, InspectionProject $dossier): JsonResponse
    {
        abort_if($dossier->inspection_plan_id === null, 404);

        return $this->recordMeasure($request, $dossier, null);
    }

    public function close(InspectionProject $dossier): JsonResponse
    {
        $this->inspection->close($dossier);

        return response()->json(['summary' => $this->inspection->summaryOf($dossier->fresh())]);
    }

    public function nonConformity(InspectionProject $dossier, InspectionMeasure $measure): JsonResponse
    {
        return $this->declareNc($dossier, $measure);
    }

    // ------------------------------------------------------------------
    // Onglet Contrôle de l'écran tâche
    // ------------------------------------------------------------------

    public function taskShow(int $id): JsonResponse
    {
        $task = $this->task($id);
        $dossier = $this->inspection->dossierFor($task, create: true);
        abort_if($dossier === null, 404, __('inspection.no_plan_for_task'));

        return response()->json($this->inspection->sheetOf($dossier, $task) + ['trans' => InspectionPlanController::trans()]);
    }

    public function taskMeasure(Request $request, int $id): JsonResponse
    {
        $task = $this->task($id);
        $dossier = $this->inspection->dossierFor($task, create: true);
        abort_if($dossier === null, 404, __('inspection.no_plan_for_task'));

        return $this->recordMeasure($request, $dossier, $task);
    }

    public function taskClose(int $id): JsonResponse
    {
        $task = $this->task($id);
        $dossier = $this->inspection->dossierFor($task);
        abort_if($dossier === null, 404);
        $this->inspection->close($dossier);

        return response()->json(['summary' => $this->inspection->summary($task)]);
    }

    public function taskNonConformity(int $id, InspectionMeasure $measure): JsonResponse
    {
        $dossier = $this->inspection->dossierFor($this->task($id));
        abort_if($dossier === null, 404);

        return $this->declareNc($dossier, $measure);
    }

    // ------------------------------------------------------------------

    private function recordMeasure(Request $request, InspectionProject $dossier, ?Task $task): JsonResponse
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

        $measure = $this->inspection->record(
            $dossier,
            $task,
            InspectionControlPoint::findOrFail($data['point_id']),
            (int) $data['part'],
            isset($data['value']) ? (float) $data['value'] : null,
            $data['result'] ?? null,
            $data['device_id'] ?? null,
            $data['comment'] ?? null,
        );

        return response()->json([
            'measure' => $this->inspection->measureRow($measure, $request->user()?->name),
            'summary' => $task ? $this->inspection->summary($task) : $this->inspection->summaryOf($dossier),
        ]);
    }

    private function declareNc(InspectionProject $dossier, InspectionMeasure $measure): JsonResponse
    {
        abort_unless((int) $measure->Session?->inspection_project_id === (int) $dossier->id, 404);
        abort_if($measure->result !== 'nok', 422, __('inspection.nc_only_nok'));

        $nc = $this->inspection->declareNonConformity($measure);

        return response()->json([
            'nc'  => ['id' => $nc->id, 'code' => $nc->code],
            'url' => route('quality.nonConformitie', ['search' => $nc->code]),
        ]);
    }

    private function task(int $id): Task
    {
        return Task::with('OrderLines.Product', 'OrderLines.order')->findOrFail($id);
    }
}
