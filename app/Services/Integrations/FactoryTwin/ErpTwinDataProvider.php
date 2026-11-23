<?php

namespace App\Services\Integrations\FactoryTwin;

use Illuminate\Support\Facades\Cache;
use Nestprod\FactoryTwin\Contracts\TwinDataProvider;

/**
 * Fournisseur de données du package factory-twin (commercial, hors dépôt).
 *
 * Chargé seulement quand le package est présent (FactoryTwinServiceProvider) :
 * l'interface n'existe pas dans une installation open source. Toute la logique
 * est dans FactoryTwinSnapshot, testée sans le package.
 */
class ErpTwinDataProvider implements TwinDataProvider
{
    public function __construct(private readonly FactoryTwinSnapshot $snapshot)
    {
    }

    public function snapshot(): array
    {
        // Plusieurs écrans d'atelier rafraîchissent toutes les 30 s : un seul calcul pour tous.
        $seconds = (int) config('services.factory_twin.cache_seconds', 15);

        return $seconds > 0
            ? Cache::remember('factory-twin.snapshot', $seconds, fn () => $this->snapshot->snapshot())
            : $this->snapshot->snapshot();
    }

    public function orderTrace(string $ref): array
    {
        return $this->snapshot->orderTrace($ref);
    }

    public function initialScene(): ?array
    {
        // Aucun plan d'atelier dans l'ERP : le jumeau range les postes en grille.
        return null;
    }
}
