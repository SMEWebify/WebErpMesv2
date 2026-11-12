<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\QuoteLines;
use App\Services\Integrations\Nest2Bend\BendingSimulationService;
use App\Services\Integrations\Nest2Bend\Exceptions\Nest2BendException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * « Simuler » sur une ligne de devis (NEST2BEND en iframe).
 *
 * Le navigateur n'appelle jamais NEST2BEND avec la clé : il demande ici une session, ouvre
 * l'URL reçue, puis renvoie ici le resultId que l'iframe lui a signalé.
 */
class BendingSimulationController extends Controller
{
    public function __construct(private readonly BendingSimulationService $service) {}

    public function store(Request $request, int $quoteId, int $id): JsonResponse
    {
        $line = $this->line($quoteId, $id);

        try {
            $session = $this->service->start($line, $request->user(), $request->getSchemeAndHttpHost(), app()->getLocale());
        } catch (Nest2BendException $e) {
            return $this->error($e);
        }

        return response()->json($session, 201)->header('Cache-Control', 'no-store');
    }

    public function complete(Request $request, int $quoteId, int $id): JsonResponse
    {
        $line = $this->line($quoteId, $id);
        $validated = $request->validate(['result_id' => ['required', 'uuid']]);

        try {
            $result = $this->service->complete($line, $request->user(), $validated['result_id']);
        } catch (Nest2BendException $e) {
            return $this->error($e);
        }

        return response()->json([
            'message'          => __('nest2bend.result_saved', ['summary' => $result['summary']]),
            'summary'          => $result['summary'],
            'internal_comment' => $result['internal_comment'],
            'last'             => $result['last'],
        ]);
    }

    /** « Récupérer la fiche » : nouvel essai de téléchargement de la fiche de réglage PDF. */
    public function sheet(Request $request, int $quoteId, int $id): JsonResponse
    {
        $line = $this->line($quoteId, $id);

        try {
            $last = $this->service->retrySheet($line, $request->user());
        } catch (Nest2BendException $e) {
            return $this->error($e);
        }

        return response()->json([
            'message' => $last['sheet'] === \App\Models\Workflow\BendingSimulation::SHEET_STORED
                ? __('nest2bend.sheet.stored', ['name' => $last['sheet_name']])
                : $last['sheet_error'],
            'last'    => $last,
        ], $last['sheet'] === \App\Models\Workflow\BendingSimulation::SHEET_STORED ? 200 : 502);
    }

    private function line(int $quoteId, int $id): QuoteLines
    {
        return QuoteLines::with('QuoteLineDetails')
            ->where('id', $id)
            ->where('quotes_id', $quoteId)
            ->firstOrFail();
    }

    /**
     * Statut renvoyé au navigateur : 403 abonnement, 422 fichier / validation (message
     * NEST2BEND), 503 indisponible. Une clé refusée (401 côté NEST2BEND) devient 409 :
     * un 401 serait pris pour la fin de la session ERP.
     */
    private function error(Nest2BendException $e): JsonResponse
    {
        $status = match ($e->kind) {
            Nest2BendException::INACTIVE    => 403,
            Nest2BendException::UNAUTHORIZED => 409,
            Nest2BendException::NOT_FOUND   => 404,
            Nest2BendException::INVALID     => 422,
            Nest2BendException::UNAVAILABLE => 503,
            Nest2BendException::RATE_LIMITED => 429,
            default                         => 502,
        };

        return response()->json(['message' => $e->getMessage(), 'reason' => $e->kind], $status);
    }
}
