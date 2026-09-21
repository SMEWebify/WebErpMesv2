<?php

namespace App\Services\Planning;

use App\Models\Planning\OperationTransitionDelay;
use Illuminate\Support\Facades\Schema;

/**
 * Résout le délai inter-opérations en heures atelier pour une paire (from, to).
 *
 * Priorité : ligne pair-spécifique dans `operation_transition_delays` → défaut
 * global (`config('planning.inter_operation_hours')`) → 0 si l'opération source
 * ou destination est inconnue. À cela s'ajoute toujours l'écart minimum
 * (`config('planning.min_operation_gap_hours')`), garanti indépendamment de
 * la paire, pour modéliser un round de dispatch atelier fixe.
 *
 * La map est chargée une seule fois par instance (résolution en O(1) dans la
 * boucle du job de planification). Ne pas partager entre requêtes : les
 * settings d'atelier peuvent changer.
 */
class InterOperationDelayResolver
{
    /** @var array<string, float>|null Map "fromId|toId" → transfer_hours */
    private ?array $pairMap = null;

    private ?float $defaultHours = null;

    private ?float $minGapHours = null;

    public function __construct()
    {
        $this->defaultHours = (float) config('planning.inter_operation_hours', 0);
        $this->minGapHours  = (float) config('planning.min_operation_gap_hours', 0);
    }

    /**
     * Renvoie le délai total (transfert + écart minimum) à appliquer entre
     * deux tâches consécutives, en heures.
     */
    public function hoursBetween(?int $fromServiceId, ?int $toServiceId): float
    {
        return $this->transferHoursBetween($fromServiceId, $toServiceId) + $this->minGapHours;
    }

    /**
     * Composante « transfert » seule, hors écart minimum — utilisée par les
     * tests pour vérifier la priorité pair → défaut.
     */
    public function transferHoursBetween(?int $fromServiceId, ?int $toServiceId): float
    {
        if ($fromServiceId === null || $toServiceId === null) {
            return 0.0;
        }

        $key = $fromServiceId . '|' . $toServiceId;
        $map = $this->getPairMap();

        return $map[$key] ?? $this->defaultHours;
    }

    private function getPairMap(): array
    {
        if ($this->pairMap !== null) {
            return $this->pairMap;
        }

        // Défensif : les tests unitaires isolés ne recréent pas toujours la table
        // (cf. TaskDateCalculatorTest et son SQLite in-memory). Sans ce garde,
        // le simple fait d'instancier le resolver casserait ces tests.
        if (!Schema::hasTable('operation_transition_delays')) {
            return $this->pairMap = [];
        }

        $this->pairMap = OperationTransitionDelay::query()
            ->select(['from_service_id', 'to_service_id', 'transfer_hours'])
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->from_service_id . '|' . $row->to_service_id => (float) $row->transfer_hours,
            ])
            ->all();

        return $this->pairMap;
    }
}
