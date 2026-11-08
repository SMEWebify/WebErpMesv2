<?php

namespace App\Http\Controllers\Quality;

use App\Models\Methods\MethodsServices;
use App\Models\Quality\QualityNonConformity;
use App\Services\Quality\NonConformityImpact;
use App\Services\Quality\NonConformityImpactException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Onglet « Impact » de la fiche NC : coûts, délai, ordre de retouche, report de
 * la date de livraison ; et indicateur coût de non-qualité de l'index des NC.
 * Toute la règle est dans NonConformityImpact.
 */
class NonConformityImpactController extends Controller
{
    public function __construct(private readonly NonConformityImpact $impact)
    {
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->payload(QualityNonConformity::findOrFail($id)));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $nc = QualityNonConformity::findOrFail($id);

        $data = $request->validate([
            'cost_internal'  => 'nullable|numeric|min:0|max:9999999999',
            'cost_external'  => 'nullable|numeric|min:0|max:9999999999',
            'cost_scrap'     => 'nullable|numeric|min:0|max:9999999999',
            'delay_days'     => 'nullable|integer|min:0|max:3650',
            'impact_comment' => 'nullable|string|max:5000',
        ]);

        $this->impact->updateImpact($nc, $data);

        return response()->json(['message' => __('nc_impact.saved')] + $this->payload($nc->fresh()));
    }

    public function rework(Request $request, int $id): JsonResponse
    {
        $nc = QualityNonConformity::findOrFail($id);

        $data = $request->validate([
            'operations'                       => 'required|array|min:1|max:20',
            'operations.*.methods_services_id' => 'required|integer',
            'operations.*.hours'               => 'nullable|numeric|min:0|max:10000',
            'operations.*.label'               => 'nullable|string|max:255',
        ]);

        try {
            $tasks = $this->impact->createReworkOrder($nc, $data['operations'], $request->user());
        } catch (NonConformityImpactException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json([
            'message' => __('nc_impact.rework.created', ['count' => $tasks->count()]),
        ] + $this->payload($nc->fresh()), 201);
    }

    public function delay(Request $request, int $id): JsonResponse
    {
        $nc = QualityNonConformity::findOrFail($id);

        $data = $request->validate(['delivery_date' => 'nullable|date']);

        try {
            $line = $this->impact->applyDelay($nc, $data['delivery_date'] ?? null, $request->user());
        } catch (NonConformityImpactException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json([
            'message' => __('nc_impact.delay.applied', ['date' => \Carbon\Carbon::parse($line->delivery_date)->format('d/m/Y')]),
        ] + $this->payload($nc->fresh()));
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->impact->nonQualityCosts());
    }

    private function payload(QualityNonConformity $nc): array
    {
        return [
            'impact'     => $this->impact->impact($nc),
            'can_rework' => (bool) request()->user()?->can(NonConformityImpact::PERMISSION),
            'services'   => MethodsServices::query()
                ->whereIn('type', NonConformityImpact::REWORK_SERVICE_TYPES)
                ->orderBy('ordre')
                ->get(['id', 'code', 'label', 'hourly_rate']),
            'task_url'   => preg_replace('/\/[^\/]+$/', '', route('production.task.statu.id', ['id' => 1])),
        ];
    }
}
