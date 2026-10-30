<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\AffairRisk;
use App\Models\Workflow\Opportunities;
use App\Services\Affairs\AffairRiskException;
use App\Services\Affairs\AffairRiskRegister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Onglet « Risques » de la fiche opportunité (règles dans AffairRiskRegister).
 * Lecture : accès de la fiche ; écriture : `opportunities-menu` (middleware de route).
 */
class AffairRisksController extends Controller
{
    public function __construct(private AffairRiskRegister $register)
    {
    }

    public function index(Opportunities $id): JsonResponse
    {
        return response()->json($this->register->register($id, auth()->user()));
    }

    public function store(Request $request, Opportunities $id): JsonResponse
    {
        return $this->respond(fn () => $this->register->save($id, null, $this->validated($request), $request->user()), $id, 201);
    }

    public function update(Request $request, Opportunities $id, AffairRisk $risk): JsonResponse
    {
        abort_unless((int) $risk->opportunities_id === (int) $id->id, 404);

        return $this->respond(fn () => $this->register->save($id, $risk, $this->validated($request), $request->user()), $id);
    }

    private function validated(Request $request): array
    {
        $scale = 'required|integer|between:' . AffairRiskRegister::SCALE_MIN . ',' . AffairRiskRegister::SCALE_MAX;

        return $request->validate([
            'label'       => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'category'    => ['required', 'string', Rule::in(AffairRiskRegister::categories())],
            'probability' => $scale,
            'severity'    => $scale,
            'mitigation'  => 'nullable|string|max:10000',
            'owner_id'    => 'nullable|integer|exists:users,id',
            'due_date'    => 'nullable|date',
            'status'      => ['required', Rule::in(AffairRiskRegister::STATUSES)],
            'orders_id'   => 'nullable|integer',
        ]);
    }

    private function respond(callable $action, Opportunities $opportunity, int $status = 200): JsonResponse
    {
        try {
            $action();
        } catch (AffairRiskException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json($this->register->register($opportunity, auth()->user()), $status);
    }
}
