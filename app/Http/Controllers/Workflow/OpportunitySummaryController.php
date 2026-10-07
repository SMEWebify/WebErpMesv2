<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\Opportunities;
use App\Services\Affairs\AffairSummaryService;
use App\Services\Affairs\AffairTimelineService;
use Illuminate\Http\JsonResponse;

/**
 * Synthèse et historique d'une affaire (opportunité), en JSON pour l'onglet
 * « Synthèse » de la fiche opportunité.
 *
 * Accès : celui de la fiche (groupe de routes opportunités). Les sections
 * d'un module dont l'utilisateur n'a pas le menu sont retirées de la réponse,
 * comme pour la timeline de la fiche société.
 */
class OpportunitySummaryController extends Controller
{
    private const SECTION_PERMISSIONS = [
        'quotes'     => 'quotes-menu',
        'orders'     => 'orders-menu',
        'purchases'  => 'purchases-menu',
        'deliveries' => 'deliverys-menu',
        'invoices'   => 'invoices-menu',
    ];

    public function summary(Opportunities $id, AffairSummaryService $service): JsonResponse
    {
        return response()->json($service->summarize($id, $this->visibleSections()));
    }

    public function timeline(Opportunities $id, AffairTimelineService $service): JsonResponse
    {
        return response()->json(['data' => $service->timeline($id, $this->visibleSections())]);
    }

    /** @return string[] */
    private function visibleSections(): array
    {
        $user = auth()->user();

        return array_keys(array_filter(self::SECTION_PERMISSIONS, fn ($permission) => $user->can($permission)));
    }
}
