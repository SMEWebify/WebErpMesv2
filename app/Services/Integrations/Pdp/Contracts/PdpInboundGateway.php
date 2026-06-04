<?php

namespace App\Services\Integrations\Pdp\Contracts;

use Illuminate\Http\Request;

/**
 * Contrat OPTIONNEL pour les drivers PDP capables de RECEVOIR des factures
 * fournisseurs entrantes (obligation au 1er sept. 2026).
 *
 * Le parsing du Factur-X est commun (FacturXReader) ; seul le transport est
 * spécifique au fournisseur. Un driver implémente l'une et/ou l'autre méthode
 * selon ce que son API expose :
 *   - parseInboundWebhook : la PDP pousse les documents (webhook).
 *   - fetchInbound        : on interroge la PDP pour les documents en attente.
 *
 * Chaque entrée retournée est un tableau ['external_id' => ?string, 'content' => string]
 * où `content` est le PDF/A-3 ou le XML CII brut, prêt pour FacturXReader.
 */
interface PdpInboundGateway
{
    /**
     * @return array{external_id: ?string, content: string}|null  null si non pertinent
     *
     * @throws \App\Services\Integrations\Pdp\Exceptions\PdpSignatureException
     */
    public function parseInboundWebhook(Request $request): ?array;

    /**
     * @return array<int, array{external_id: ?string, content: string}>
     */
    public function fetchInbound(int $tenantId): array;
}
