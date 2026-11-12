<?php

namespace App\Services\Integrations\Nest2Bend\Data;

use Carbon\CarbonImmutable;

/**
 * Réponse 200 de GET /api/v1/compte.
 */
final class AccountState
{
    public function __construct(
        public readonly ?string $societe,
        public readonly ?string $etat,
        public readonly bool $peutEcrire,
        public readonly ?CarbonImmutable $echeance,
        public readonly array $modules = [],
        public readonly ?int $pressesMax = null,
    ) {}

    public static function fromArray(array $body): self
    {
        $droits = is_array($body['droits'] ?? null) ? $body['droits'] : [];

        $echeance = null;
        if (filled($droits['echeance'] ?? null)) {
            try {
                $echeance = CarbonImmutable::parse((string) $droits['echeance']);
            } catch (\Throwable) {
                $echeance = null;
            }
        }

        return new self(
            societe: is_string($body['societe'] ?? null) ? $body['societe'] : null,
            etat: is_string($droits['etat'] ?? null) ? $droits['etat'] : null,
            // Absent ou mal typé = pas de droit d'écriture : on ne débloque rien par défaut.
            peutEcrire: ($droits['peut_ecrire'] ?? false) === true,
            echeance: $echeance,
            modules: array_values(array_filter((array) ($droits['modules'] ?? []), 'is_string')),
            pressesMax: is_numeric($droits['presses_max'] ?? null) ? (int) $droits['presses_max'] : null,
        );
    }
}
