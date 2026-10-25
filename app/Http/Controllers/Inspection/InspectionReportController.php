<?php

namespace App\Http\Controllers\Inspection;

use App\Http\Controllers\Controller;
use App\Models\Inspection\InspectionProject;
use App\Services\Inspection\FaiReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Rapport premier article d'un dossier de contrôle : saisie des champs sans
 * source, signatures « vérifié par » (inspection-plans-edit) puis « approuvé
 * par » (inspection-plans-approve), génération du PDF rangé dans la GED.
 * Un rapport approuvé est figé : retirer l'approbation le rouvre.
 */
class InspectionReportController extends Controller
{
    public function __construct(private readonly FaiReport $report)
    {
    }

    public function page(InspectionProject $dossier)
    {
        abort_if($dossier->inspection_plan_id === null, 404);

        return view('quality/quality-inspection-report', [
            'dossier'   => $dossier,
            'trans'     => InspectionPlanController::trans(),
            'endpoints' => [
                'show'     => route('inspection.reports.show', ['dossier' => $dossier->id]),
                'update'   => route('inspection.reports.update', ['dossier' => $dossier->id]),
                'sign'     => route('inspection.reports.sign', ['dossier' => $dossier->id]),
                'generate' => route('inspection.reports.generate', ['dossier' => $dossier->id]),
                'control'  => $dossier->order_lines_id === null
                    ? route('quality.inspections.show', ['dossier' => $dossier->id])
                    : null,
            ],
        ]);
    }

    public function show(Request $request, InspectionProject $dossier): JsonResponse
    {
        abort_if($dossier->inspection_plan_id === null, 404);
        $user = $request->user();

        return response()->json($this->report->build($dossier) + [
            'can_verify'  => $user->can(InspectionPlanController::EDIT_PERMISSION),
            'can_approve' => $user->can(InspectionPlanController::APPROVE_PERMISSION),
        ]);
    }

    public function update(Request $request, InspectionProject $dossier): JsonResponse
    {
        abort_if($dossier->approved_at !== null, 409, __('inspection.report_locked'));

        $data = $request->validate([
            'fields'               => ['sometimes', 'array'],
            'fields.*'             => ['nullable', 'string', 'max:255'],
            'fai_type'             => ['sometimes', 'in:full,partial'],
            'report_comment'       => ['sometimes', 'nullable', 'string', 'max:5000'],
            'customer_approval'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer_approved_at' => ['sometimes', 'nullable', 'date'],
        ]);

        if (isset($data['fields'])) {
            $fields = array_intersect_key($data['fields'], array_flip(FaiReport::EDITABLE));
            $data['report_fields'] = array_merge($dossier->report_fields ?? [], $fields);
            unset($data['fields']);
        }

        $dossier->update($data);

        return $this->show($request, $dossier->fresh());
    }

    /**
     * Signe (ou retire la signature) : `role` = verified | approved, `sign` = bool.
     * L'approbation suppose la vérification ; retirer la vérification retire
     * aussi l'approbation.
     */
    public function sign(Request $request, InspectionProject $dossier): JsonResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:verified,approved'],
            'sign' => ['required', 'boolean'],
        ]);
        $user = $request->user();

        if ($data['role'] === 'verified') {
            abort_unless($user->can(InspectionPlanController::EDIT_PERMISSION), 403);
            abort_if($dossier->approved_at !== null, 409, __('inspection.report_locked'));
            $dossier->update($data['sign']
                ? ['verified_by' => $user->id, 'verified_at' => now()]
                : ['verified_by' => null, 'verified_at' => null]);
        } else {
            abort_unless($user->can(InspectionPlanController::APPROVE_PERMISSION), 403);
            abort_if($data['sign'] && $dossier->verified_at === null, 422, __('inspection.report_needs_verification'));
            $dossier->update($data['sign']
                ? ['approved_by' => $user->id, 'approved_at' => now()]
                : ['approved_by' => null, 'approved_at' => null]);
        }

        return $this->show($request, $dossier->fresh());
    }

    public function generate(Request $request, InspectionProject $dossier): JsonResponse
    {
        abort_if($dossier->inspection_plan_id === null, 404);

        $file = $this->report->generate($dossier);

        return response()->json([
            'file' => ['id' => $file->id, 'view_url' => $file->view_url, 'download_url' => $file->download_url],
        ] + $this->show($request, $dossier->fresh())->getData(true));
    }
}
