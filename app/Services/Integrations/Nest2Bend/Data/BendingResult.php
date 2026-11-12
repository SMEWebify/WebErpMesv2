<?php

namespace App\Services\Integrations\Nest2Bend\Data;

/**
 * Réponse 200 de GET /api/v1/results/{resultId}.
 *
 * Seuls `resultat.temps` (et `resultat.section` s'il est présent) sont lus : le format de
 * la séquence et des outils n'est pas encore fixé côté NEST2BEND.
 */
final class BendingResult
{
    public function __construct(
        public readonly string $resultId,
        public readonly string $sessionId,
        public readonly ?string $referenceExterne,
        public readonly ?string $politique,
        public readonly ?float $preparation,
        public readonly ?float $unitaire,
        public readonly array $compteurs = [],
        public readonly ?array $section = null,
    ) {}

    public static function fromArray(array $body): self
    {
        $resultat = is_array($body['resultat'] ?? null) ? $body['resultat'] : [];
        $temps = is_array($resultat['temps'] ?? null) ? $resultat['temps'] : [];
        $retenu = is_array($temps['retenu'] ?? null) ? $temps['retenu'] : [];

        $seconds = static fn ($value): ?float => is_numeric($value) && (float) $value >= 0 ? (float) $value : null;

        return new self(
            resultId: (string) ($body['result_id'] ?? ''),
            sessionId: (string) ($body['session_id'] ?? ''),
            referenceExterne: isset($body['reference_externe']) ? (string) $body['reference_externe'] : null,
            politique: is_string($temps['politique'] ?? null) ? $temps['politique'] : null,
            preparation: $seconds($retenu['preparation'] ?? null),
            unitaire: $seconds($retenu['unitaire'] ?? null),
            compteurs: is_array($temps['compteurs'] ?? null) ? $temps['compteurs'] : [],
            section: is_array($resultat['section'] ?? null) ? $resultat['section'] : null,
        );
    }
}
