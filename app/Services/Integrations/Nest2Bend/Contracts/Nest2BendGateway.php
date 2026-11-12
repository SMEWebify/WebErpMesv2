<?php

namespace App\Services\Integrations\Nest2Bend\Contracts;

use App\Services\Integrations\Nest2Bend\Data\AccountState;
use App\Services\Integrations\Nest2Bend\Data\BendingResult;
use App\Services\Integrations\Nest2Bend\Data\EmbedSession;
use App\Services\Integrations\Nest2Bend\Data\SetupSheet;
use App\Services\Integrations\Nest2Bend\Exceptions\Nest2BendException;

/**
 * API NEST2BEND /api/v1, appelée uniquement par le serveur de l'ERP (la clé ne sort pas).
 *
 * Driver réel : Drivers\HttpNest2BendGateway. Les tests passent par Http::fake(), ou lient
 * un autre driver à Nest2BendGatewayFactory.
 */
interface Nest2BendGateway
{
    /**
     * GET /api/v1/compte — test de la clé et état de l'abonnement.
     *
     * @throws Nest2BendException
     */
    public function account(): AccountState;

    /**
     * POST /api/v1/embed-sessions.
     *
     * @throws Nest2BendException
     */
    public function createEmbedSession(array $payload): EmbedSession;

    /**
     * GET /api/v1/results/{resultId}.
     *
     * @throws Nest2BendException
     */
    public function result(string $resultId): BendingResult;

    /**
     * GET {fiche} : fiche de réglage PDF (URL absolue lue dans le résultat). Le PDF est mis
     * en page à chaque appel. 404 : pas de fiche, ou outil / presse retiré de l'atelier ;
     * 503 : moteur indisponible.
     *
     * @throws Nest2BendException
     */
    public function setupSheet(string $url): SetupSheet;
}
