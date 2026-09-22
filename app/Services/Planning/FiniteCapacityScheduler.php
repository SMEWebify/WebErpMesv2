<?php

namespace App\Services\Planning;

use App\Models\Methods\MethodsRessources;
use App\Models\Planning\Task;
use App\Models\Workflow\OrderLines;
use App\Services\ResourceCapacityService;
use App\Services\TaskDateCalculator;
use App\Support\WorkingTime;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Ordonnanceur multi-OF avec capacité finie partagée.
 *
 * Complète le TaskDateCalculator (chaînage naïf, un OF isolé) par un contrôle
 * de capacité à la ressource et par jour. Les OF sont traités dans l'ordre
 * d'urgence, chacun accumule sa charge dans une map (ressource, jour), et une
 * tâche qui ferait déborder est décalée d'un jour ouvré à la fois (au plus tôt
 * pour ASAP, au plus tard pour ALAP), jusqu'à trouver un placement viable ou
 * à saturer la borne de sécurité — auquel cas la tâche est posée quand même,
 * flaguée en `unfitted` pour être remontée à l'utilisateur.
 *
 * L'état (charge courante, cache capacité) est porté par l'instance : à
 * réinstancier entre deux runs du job pour ne pas cumuler d'anciens plans.
 */
class FiniteCapacityScheduler
{
    public const MODE_INFINITE = 'infinite';
    public const MODE_FINITE   = 'finite';

    /** Nombre maximum de jours de décalage avant d'abandonner et poser tel quel. */
    public const MAX_SHIFT_DAYS = 60;

    /** Tolérance de comparaison capacité (round(3) sur les heures). */
    private const EPSILON = 0.001;

    /** @var array<int, array<string, float>> map [resourceId][Y-m-d] = heures consommées ce run */
    private array $dailyLoad = [];

    /** @var array<int, array<string, float>> map [resourceId][Y-m-d] = capacité disponible (cache) */
    private array $capacityCache = [];

    /** @var array<int, string[]> OF débordés lors du run, pour reporting */
    private array $unfitted = [];

    public function __construct(
        private TaskDateCalculator $calculator,
        private ResourceCapacityService $capacityService,
    ) {}

    /**
     * Réinitialise l'état — à appeler entre deux runs.
     */
    public function reset(): void
    {
        $this->dailyLoad     = [];
        $this->capacityCache = [];
        $this->unfitted      = [];
    }

    /**
     * @return array<int, string> messages "OF #N — tâche X débordée"
     */
    public function unfittedMessages(): array
    {
        return array_merge(...array_values($this->unfitted)) ?: [];
    }

    /**
     * Chaîne les tâches d'une ligne de commande avec (ou sans) contrôle capacité.
     *
     * @return array<int, array{task: Task, start: Carbon, end: Carbon}>
     */
    public function chainOrderLine(
        OrderLines $line,
        string $direction,
        string $mode,
        ?callable $delayFn = null,
    ): array {
        $anchor = $this->resolveAnchor($line, $direction);
        $tasks  = $direction === TaskDateCalculator::DIRECTION_ASAP
            ? $line->Task->sortBy('ordre')
            : $line->Task->sortByDesc('ordre');

        if ($mode !== self::MODE_FINITE) {
            return $direction === TaskDateCalculator::DIRECTION_ASAP
                ? $this->calculator->chainTasksForward($tasks, $anchor, $delayFn)
                : $this->calculator->chainTasks($tasks, $anchor, $delayFn);
        }

        return $this->chainFinite($tasks, $anchor, $direction, $delayFn, $line);
    }

    /**
     * Ancre d'une ligne selon le sens.
     */
    private function resolveAnchor(OrderLines $line, string $direction): Carbon
    {
        if ($direction === TaskDateCalculator::DIRECTION_ASAP) {
            return $line->start_date ? Carbon::parse($line->start_date) : Carbon::now();
        }

        return Carbon::parse($line->internal_delay);
    }

    /**
     * Boucle de chaînage avec capacité finie.
     *
     * @param  Collection<int, Task>  $tasks
     * @return array<int, array{task: Task, start: Carbon, end: Carbon}>
     */
    private function chainFinite(
        Collection $tasks,
        Carbon $anchor,
        string $direction,
        ?callable $delayFn,
        OrderLines $line,
    ): array {
        $isAsap = $direction === TaskDateCalculator::DIRECTION_ASAP;
        $cursor = $isAsap
            ? $this->calculator->adjustForWeekendsAndHolidaysForward($anchor->copy())
            : $this->calculator->adjustForWeekendsAndHolidays($anchor->copy());

        $results = [];
        $previousServiceId = null;

        foreach ($tasks as $task) {
            $cursor = $this->applyInterOpDelay($cursor, $task, $previousServiceId, $delayFn, $isAsap);

            $placement = $this->placeTaskWithShift($task, $cursor, $isAsap, $line);

            $results[] = ['task' => $task, 'start' => $placement['start'], 'end' => $placement['end']];

            $cursor = $isAsap ? $placement['end'] : $placement['start'];
            $previousServiceId = $task->methods_services_id ?? null;
        }

        return $results;
    }

    private function applyInterOpDelay(
        Carbon $cursor,
        Task $task,
        ?int $previousServiceId,
        ?callable $delayFn,
        bool $isAsap,
    ): Carbon {
        if ($previousServiceId === null || $delayFn === null) {
            return $cursor;
        }

        $delayHours = $isAsap
            ? (float) $delayFn($previousServiceId, $task->methods_services_id ?? null)
            : (float) $delayFn($task->methods_services_id ?? null, $previousServiceId);

        if ($delayHours <= 0) {
            return $cursor;
        }

        return $isAsap
            ? WorkingTime::addWorkingHours($cursor, $delayHours)
            : WorkingTime::subtractWorkingHours($cursor, $delayHours);
    }

    /**
     * Place une tâche en décalant d'un jour ouvré à la fois tant qu'un
     * conflit de capacité subsiste. Abandonne au bout de MAX_SHIFT_DAYS
     * (poste bouché en permanence, config incohérente) — la tâche est
     * posée à la dernière position tentée et flaguée `unfitted`.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    private function placeTaskWithShift(Task $task, Carbon $cursor, bool $isAsap, OrderLines $line): array
    {
        $lastAttempt = null;

        for ($shift = 0; $shift <= self::MAX_SHIFT_DAYS; $shift++) {
            [$start, $end] = $this->naivePlace($task, $cursor, $isAsap);
            $lastAttempt   = ['start' => $start, 'end' => $end];

            $conflict = $this->findFirstConflict($task, $start, $end);

            if ($conflict === null) {
                $this->commitLoad($task, $start, $end);
                return $lastAttempt;
            }

            // Décale le curseur d'un jour ouvré du côté opposé au conflit.
            $cursor = $isAsap
                ? $this->calculator->adjustForWeekendsAndHolidaysForward(Carbon::parse($conflict)->addDay()->startOfDay())
                : $this->calculator->adjustForWeekendsAndHolidays(Carbon::parse($conflict)->subDay()->endOfDay());
        }

        // Abandon : on pose quand même, mais on signale.
        $this->markUnfitted($task, $line);
        $this->commitLoad($task, $lastAttempt['start'], $lastAttempt['end']);
        return $lastAttempt;
    }

    /**
     * Placement naïf (infini) autour du curseur, sans regarder la charge
     * courante. C'est le même code que le TaskDateCalculator, extrait pour
     * pouvoir le rappeler à chaque shift.
     */
    private function naivePlace(Task $task, Carbon $cursor, bool $isAsap): array
    {
        $duration = (float) $task->TotalTime();

        if ($isAsap) {
            $start = $cursor->copy();
            $end   = WorkingTime::addWorkingHours($cursor, $duration);
        } else {
            $end   = $cursor->copy();
            $start = WorkingTime::subtractWorkingHours($cursor, $duration);
        }

        return [$start, $end];
    }

    /**
     * Cherche le premier jour où l'ajout de cette tâche dépasse la capacité
     * disponible d'une de ses ressources. Retourne la date au format Y-m-d,
     * ou null si tout tient.
     */
    private function findFirstConflict(Task $task, Carbon $start, Carbon $end): ?string
    {
        $resources = $this->taskResources($task);
        if ($resources->isEmpty()) {
            return null;
        }

        $hoursPerDay = $this->splitTaskHoursPerDay($start, $end);
        if ($hoursPerDay === []) {
            return null;
        }

        // Ordre chronologique : le premier conflit est celui qu'on veut décaler.
        ksort($hoursPerDay);

        foreach ($hoursPerDay as $date => $hours) {
            foreach ($resources as $resource) {
                $factor    = (float) ($resource->pivot->load_factor ?? 1);
                $load      = $hours * $factor;
                $available = $this->getResourceCapacity($resource, $date);
                $used      = $this->dailyLoad[$resource->id][$date] ?? 0.0;

                if ($used + $load > $available + self::EPSILON) {
                    return $date;
                }
            }
        }

        return null;
    }

    /**
     * Impute la charge de la tâche dans la map, une fois qu'on a décidé de la
     * poser. Ne fait rien si la tâche n'a aucune ressource.
     */
    private function commitLoad(Task $task, Carbon $start, Carbon $end): void
    {
        $resources = $this->taskResources($task);
        if ($resources->isEmpty()) {
            return;
        }

        $hoursPerDay = $this->splitTaskHoursPerDay($start, $end);

        foreach ($resources as $resource) {
            $factor = (float) ($resource->pivot->load_factor ?? 1);
            foreach ($hoursPerDay as $date => $hours) {
                $key = $resource->id;
                $this->dailyLoad[$key][$date] = round(
                    ($this->dailyLoad[$key][$date] ?? 0.0) + ($hours * $factor),
                    3
                );
            }
        }
    }

    /**
     * Découpe une plage [start, end[ en heures ouvrées par date (Y-m-d).
     * Marche heure par heure (même granularité que WorkingTime), gère les
     * fériés et régime horaire via isWorkingInstant().
     *
     * @return array<string, float>
     */
    private function splitTaskHoursPerDay(Carbon $start, Carbon $end): array
    {
        $perDay = [];
        $cursor = $start->copy();

        while ($cursor->lt($end)) {
            if (WorkingTime::isWorkingInstant($cursor)) {
                $date    = $cursor->toDateString();
                $stepEnd = $cursor->copy()->addHour();
                if ($stepEnd->gt($end)) {
                    $stepEnd = $end->copy();
                }
                $hours = $cursor->diffInSeconds($stepEnd) / 3600;
                $perDay[$date] = round(($perDay[$date] ?? 0.0) + $hours, 3);
            }
            $cursor->addHour();
        }

        return $perDay;
    }

    /**
     * Ressources de la tâche, sans re-requêter la BDD si déjà chargées.
     */
    private function taskResources(Task $task): Collection
    {
        return $task->relationLoaded('resources')
            ? $task->resources
            : $task->resources()->get();
    }

    /**
     * Capacité disponible d'une ressource un jour donné, mémorisée par
     * (ressourceId, date). L'appel à ResourceCapacityService coûte cher
     * (fériés + arrêts + absences), on ne le refait pas à chaque tâche.
     */
    private function getResourceCapacity(MethodsRessources $resource, string $date): float
    {
        if (isset($this->capacityCache[$resource->id][$date])) {
            return $this->capacityCache[$resource->id][$date];
        }

        $hours = $this->capacityService->availableHours($resource, Carbon::parse($date));
        return $this->capacityCache[$resource->id][$date] = $hours;
    }

    private function markUnfitted(Task $task, OrderLines $line): void
    {
        $orderId = $line->orders_id ?? '?';
        $this->unfitted[$line->id][] = sprintf(
            'OF #%s — tâche %s (%.1f h) débordée : capacité insuffisante sur %s',
            $orderId,
            $task->label ?? ('#' . $task->id),
            (float) $task->TotalTime(),
            $this->taskResources($task)->pluck('label')->implode(', ') ?: 'ressource inconnue',
        );
    }
}
