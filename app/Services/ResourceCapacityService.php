<?php

namespace App\Services;

use App\Models\Maintenance\WorkOrder;
use App\Models\Methods\MethodsRessources;
use App\Models\Times\TimesAbsence;
use App\Models\Times\TimesBanckHoliday;
use Carbon\Carbon;

/**
 * Capacité réellement disponible d'une ressource un jour donné.
 *
 * Trois couches, dans cet ordre :
 *   1. les heures ouvertes par le régime horaire (lot 4a) ;
 *   2. les jours fériés, qui les annulent ;
 *   3. les indisponibilités datées — arrêts machine pour les machines, absences
 *      validées pour la main-d'œuvre.
 *
 * Ces sources existaient déjà toutes dans l'ERP mais aucune n'était consommée
 * par la planification : la capacité était un simple `capacity / 5`.
 *
 * Les données par ressource sont chargées une fois et mémorisées : le service est
 * appelé en boucle sur des milliers de couples (ressource, jour).
 */
class ResourceCapacityService
{
    /** Statut « validée » d'une demande d'absence (1 = à valider, 3 = refusée). */
    public const ABSENCE_VALIDATED = 2;

    /** Au-delà, on considère que la tâche ne tient pas dans l'horizon planifiable. */
    public const DEFAULT_HORIZON_DAYS = 60;

    /** @var array<int, \Illuminate\Support\Collection> */
    private array $downtimeCache = [];

    /** @var array<int, \Illuminate\Support\Collection> */
    private array $absenceCache = [];

    /** @var array<int, int> */
    private array $headcountCache = [];

    /** Heures ouvertes par le calendrier, hors indisponibilités. */
    public function openHours(MethodsRessources $resource, Carbon $date): float
    {
        if (TimesBanckHoliday::isBankHoliday($date)) {
            return 0.0;
        }

        return (float) $resource->dailyCapacity($date);
    }

    /** Heures réellement disponibles : ouverture moins indisponibilités. */
    public function availableHours(MethodsRessources $resource, Carbon $date): float
    {
        $open = $this->openHours($resource, $date);

        if ($open <= 0) {
            return 0.0;
        }

        return max(0.0, round($open - $this->unavailableHours($resource, $date, $open), 3));
    }

    /**
     * Indisponibilités du jour, bornées aux heures d'ouverture : une machine
     * arrêtée 24 h ne retire pas plus que ce qu'elle ouvrait.
     */
    public function unavailableHours(MethodsRessources $resource, Carbon $date, ?float $openHours = null): float
    {
        $open = $openHours ?? $this->openHours($resource, $date);

        if ($open <= 0) {
            return 0.0;
        }

        $lost = $resource->is_labor
            ? $this->laborLostHours($resource, $date, $open)
            : $this->machineLostHours($resource, $date);

        return min($open, round($lost, 3));
    }

    /**
     * Arrêts machine : les ordres de travaux qui déclarent la machine arrêtée,
     * atteints depuis la ressource via les immobilisations (assets).
     * On prend la fenêtre réelle quand elle est connue, la fenêtre prévue sinon.
     */
    private function machineLostHours(MethodsRessources $resource, Carbon $date): float
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $dayStart->copy()->addDay();
        $lost = 0.0;

        foreach ($this->downtimeFor($resource) as $workOrder) {
            [$start, $end] = $this->downtimeWindow($workOrder);

            if (! $start || ! $end || $end->lessThanOrEqualTo($dayStart) || $start->greaterThanOrEqualTo($dayEnd)) {
                continue;
            }

            $from = $start->greaterThan($dayStart) ? $start : $dayStart;
            $to = $end->lessThan($dayEnd) ? $end : $dayEnd;

            $lost += $from->diffInSeconds($to) / 3600;
        }

        return $lost;
    }

    /**
     * Absences : la capacité d'un poste manuel baisse au prorata des personnes
     * habilitées absentes ce jour-là. Sans personne rattachée, on ne sait rien
     * et on ne retire rien.
     */
    private function laborLostHours(MethodsRessources $resource, Carbon $date, float $openHours): float
    {
        $headcount = $this->headcountFor($resource);

        if ($headcount === 0) {
            return 0.0;
        }

        $absent = $this->absencesFor($resource)
            ->filter(fn (TimesAbsence $absence) => $date->betweenIncluded(
                Carbon::parse($absence->start_date)->startOfDay(),
                Carbon::parse($absence->end_date)->endOfDay()
            ))
            ->pluck('user_id')
            ->unique()
            ->count();

        return $openHours * min(1, $absent / $headcount);
    }

    /**
     * Répartit des heures sur les jours ouverts successifs à partir d'une date.
     *
     * Le calcul remplace l'imputation de la totalité sur le seul `start_date` :
     * une tâche de 20 h sur une ressource en 1×8 occupe trois jours.
     *
     * @param callable(string): float $residualForDate capacité restante d'un jour
     * @return array<string, float>|null null si la charge ne tient pas dans l'horizon
     */
    public function spreadHours(
        float $hours,
        Carbon $from,
        callable $residualForDate,
        int $horizonDays = self::DEFAULT_HORIZON_DAYS
    ): ?array {
        if ($hours <= 0) {
            return [$from->toDateString() => 0.0];
        }

        $plan = [];
        $remaining = $hours;
        $cursor = $from->copy()->startOfDay();

        for ($day = 0; $day < $horizonDays && $remaining > 0.0001; $day++) {
            $date = $cursor->toDateString();
            $residual = max(0.0, (float) $residualForDate($date));

            if ($residual > 0) {
                $booked = min($residual, $remaining);
                $plan[$date] = round($booked, 3);
                $remaining -= $booked;
            }

            $cursor->addDay();
        }

        return $remaining > 0.0001 ? null : $plan;
    }

    /** Fenêtre d'immobilisation d'un ordre de travaux : réelle si connue, prévue sinon. */
    private function downtimeWindow(WorkOrder $workOrder): array
    {
        if ($workOrder->started_at) {
            $start = Carbon::parse($workOrder->started_at);
            $end = $workOrder->finished_at
                ? Carbon::parse($workOrder->finished_at)
                : $start->copy()->addMinutes($workOrder->actual_duration_minutes ?? $workOrder->estimated_duration_minutes ?? 0);

            return [$start, $end->greaterThan($start) ? $end : $start->copy()->endOfDay()];
        }

        if ($workOrder->scheduled_at) {
            $start = Carbon::parse($workOrder->scheduled_at)->startOfDay();
            $end = $workOrder->estimated_duration_minutes
                ? $start->copy()->addMinutes($workOrder->estimated_duration_minutes)
                : $start->copy()->endOfDay();

            return [$start, $end];
        }

        return [null, null];
    }

    private function downtimeFor(MethodsRessources $resource)
    {
        return $this->downtimeCache[$resource->id] ??= WorkOrder::query()
            ->where('machine_stopped', true)
            ->whereHas('asset', fn ($query) => $query->where('methods_ressource_id', $resource->id))
            ->get(['id', 'scheduled_at', 'started_at', 'finished_at', 'estimated_duration_minutes', 'actual_duration_minutes']);
    }

    private function absencesFor(MethodsRessources $resource)
    {
        return $this->absenceCache[$resource->id] ??= TimesAbsence::query()
            ->where('statu', self::ABSENCE_VALIDATED)
            ->whereIn('user_id', $resource->users()->pluck('users.id'))
            ->get(['user_id', 'start_date', 'end_date']);
    }

    private function headcountFor(MethodsRessources $resource): int
    {
        return $this->headcountCache[$resource->id] ??= $resource->users()->count();
    }
}
