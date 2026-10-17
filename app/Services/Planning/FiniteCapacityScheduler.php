<?php

namespace App\Services\Planning;

use App\Enums\TaskDependencyLagUnit;
use App\Enums\TaskDependencyType;
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
 * Deux chemins : chainOrderLine() enchaîne les opérations par `ordre` (le
 * comportement historique, inchangé) ; chainOrderLineWithDependencies() suit
 * les liens saisis entre opérations (fin-début, début-début, fin-fin, avec
 * décalage). Le job n'emprunte le second que pour une ligne qui a au moins un
 * lien saisi.
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

    /** @var array<int, string[]> liens non tenus, circulaires ou ignorés, pour reporting */
    private array $conflicts = [];

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
        $this->conflicts     = [];
    }

    /**
     * @return array<int, string> messages "OF #N — tâche X débordée"
     */
    public function unfittedMessages(): array
    {
        return array_merge(...array_values($this->unfitted)) ?: [];
    }

    /**
     * @return array<int, string> messages sur les liens entre opérations
     */
    public function conflictMessages(): array
    {
        return array_merge(...array_values($this->conflicts)) ?: [];
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
        ?Carbon $asapFrom = null,
    ): array {
        $anchor = $this->resolveAnchor($line, $direction, $asapFrom);
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
     * Place les opérations d'une ligne en suivant leurs liens.
     *
     * Une opération sans lien saisi suit la précédente (fin-début implicite) :
     * sur une gamme sans aucun lien, le résultat est celui de chainOrderLine(),
     * à la seconde près. Une tâche datée « Ne pas recalculer », démarrée ou
     * terminée est un point fixe : ses voisines la respectent, elle-même ne
     * bouge pas et n'est pas renvoyée.
     * Liens circulaires : retour au chaînage par `ordre`, et message.
     *
     * @param  Collection<int, Task>  $tasks  opérations planifiables de la ligne,
     *                                         figées comprises, liens `predecessorLinks` chargés
     * @return array<int, array{task: Task, start: Carbon, end: Carbon}> opérations non figées
     */
    public function chainOrderLineWithDependencies(
        OrderLines $line,
        Collection $tasks,
        string $direction,
        string $mode,
        ?callable $delayFn = null,
        ?Carbon $asapFrom = null,
    ): array {
        $graph = DependencyGraph::fromTasks($tasks);

        foreach ($graph->ignoredLinks() as $link) {
            $this->conflicts[$line->id][] = sprintf(
                'OF #%s — lien ignoré : la tâche #%s n\'est pas dans la gamme de la ligne',
                $line->orders_id ?? '?',
                $link->predecessor_task_id,
            );
        }

        if ($graph->hasCycle()) {
            $this->conflicts[$line->id][] = sprintf(
                'OF #%s — liens circulaires (%s) : ordre de gamme utilisé',
                $line->orders_id ?? '?',
                $graph->describeCycle(),
            );

            return $this->chainOrderLine($line, $direction, $mode, $delayFn, $asapFrom);
        }

        $isAsap = $direction === TaskDateCalculator::DIRECTION_ASAP;
        $finite = $mode === self::MODE_FINITE;
        $anchor = $isAsap
            ? $this->calculator->adjustForWeekendsAndHolidaysForward($this->resolveAnchor($line, $direction, $asapFrom))
            : $this->calculator->adjustForWeekendsAndHolidays($this->resolveAnchor($line, $direction, $asapFrom));

        /** @var array<int, array{start: Carbon, end: Carbon}> $placed */
        $placed = [];
        foreach ($graph->tasks() as $id => $task) {
            if ($this->isFrozen($task)) {
                $placed[$id] = $this->frozenInterval($task);
            }
        }

        $results = [];
        $order = $isAsap ? $graph->topologicalOrder() : $graph->reverseTopologicalOrder();

        foreach ($order as $id) {
            if (isset($placed[$id])) {
                continue;
            }

            $task = $graph->task($id);

            if ($isAsap) {
                $cursor = $anchor->copy();
                foreach ($graph->incoming($id) as $edge) {
                    $earliest = $this->earliestStart($edge, $graph->task($edge->from), $task, $placed[$edge->from], $delayFn);
                    if ($earliest->gt($cursor)) {
                        $cursor = $earliest;
                    }
                }
            } else {
                $cursor = $anchor->copy();
                foreach ($graph->outgoing($id) as $edge) {
                    $latest = $this->latestEnd($edge, $task, $graph->task($edge->to), $placed[$edge->to], $delayFn);
                    if ($latest->lt($cursor)) {
                        $cursor = $latest;
                    }
                }
            }

            $placement = $finite
                ? $this->placeTaskWithShift($task, $cursor, $isAsap, $line)
                : $this->place($task, $cursor, $isAsap);

            $placed[$id] = $placement;
            $results[] = ['task' => $task, 'start' => $placement['start'], 'end' => $placement['end']];
        }

        $this->checkFrozenLinks($graph, $placed, $line, $delayFn, $isAsap);

        return $results;
    }

    /**
     * Début au plus tôt que le lien impose au successeur : délai
     * inter-opérations, puis décalage, quel que soit le type de lien.
     *
     * @param  array{start: Carbon, end: Carbon}  $from
     */
    private function earliestStart(DependencyEdge $edge, Task $predecessor, Task $successor, array $from, ?callable $delayFn): Carbon
    {
        $shift = $this->transferHours($predecessor, $successor, $delayFn, true);
        $lag   = $this->lagHours($edge, $predecessor, $successor);

        return match ($edge->type) {
            TaskDependencyType::StartToStart   => $this->addHours($this->addHours($from['start'], $shift), $lag),
            TaskDependencyType::FinishToFinish => WorkingTime::subtractWorkingHours(
                $this->addHours($this->addHours($from['end'], $shift), $lag),
                $successor->plannedHours(),
            ),
            default                            => $this->addHours($this->addHours($from['end'], $shift), $lag),
        };
    }

    /**
     * Fin au plus tard que le lien impose au prédécesseur : même règle, en
     * remontant le temps.
     *
     * @param  array{start: Carbon, end: Carbon}  $to
     */
    private function latestEnd(DependencyEdge $edge, Task $predecessor, Task $successor, array $to, ?callable $delayFn): Carbon
    {
        $shift = $this->transferHours($predecessor, $successor, $delayFn, false);
        $lag   = $this->lagHours($edge, $predecessor, $successor);

        return match ($edge->type) {
            TaskDependencyType::StartToStart   => WorkingTime::addWorkingHours(
                $this->subtractHours($this->subtractHours($to['start'], $lag), $shift),
                $predecessor->plannedHours(),
            ),
            TaskDependencyType::FinishToFinish => $this->subtractHours($this->subtractHours($to['end'], $lag), $shift),
            default                            => $this->subtractHours($this->subtractHours($to['start'], $lag), $shift),
        };
    }

    /**
     * Délai inter-opérations entre deux opérations liées, pour tout type de
     * lien. Règle historique d'omission : en avançant (ASAP), pas de délai si
     * le service amont est inconnu ; en remontant (ALAP), si le service aval
     * l'est. Sur un fin-début, c'est exactement le délai du chaînage par `ordre`.
     */
    private function transferHours(Task $predecessor, Task $successor, ?callable $delayFn, bool $isAsap): float
    {
        if ($delayFn === null) {
            return 0.0;
        }

        $known = $isAsap ? $predecessor->methods_services_id : $successor->methods_services_id;
        if ($known === null) {
            return 0.0;
        }

        return max(0.0, (float) $delayFn($predecessor->methods_services_id ?? null, $successor->methods_services_id ?? null));
    }

    /**
     * Décalage en heures ouvrées. En pièces : sur un début-début, le temps que
     * les N premières pièces sortent de l'opération amont (réglage compris) ;
     * sur un fin-fin, le temps que l'opération aval traite les N dernières.
     */
    private function lagHours(DependencyEdge $edge, Task $predecessor, Task $successor): float
    {
        if ($edge->lagValue <= 0) {
            return 0.0;
        }

        if ($edge->lagUnit === TaskDependencyLagUnit::Hour) {
            return $edge->lagValue;
        }

        return match ($edge->type) {
            TaskDependencyType::StartToStart   => (float) $predecessor->seting_time + $edge->lagValue * (float) $predecessor->unit_time,
            TaskDependencyType::FinishToFinish => $edge->lagValue * (float) $successor->unit_time,
            default                            => 0.0,
        };
    }

    private function addHours(Carbon $date, float $hours): Carbon
    {
        return $hours > 0 ? WorkingTime::addWorkingHours($date, $hours) : $date->copy();
    }

    private function subtractHours(Carbon $date, float $hours): Carbon
    {
        return $hours > 0 ? WorkingTime::subtractWorkingHours($date, $hours) : $date->copy();
    }

    /**
     * Placement en capacité infinie, même calcul que TaskDateCalculator.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    private function place(Task $task, Carbon $cursor, bool $isAsap): array
    {
        [$start, $end] = $this->naivePlace($task, $cursor, $isAsap);

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Point fixe : tâche « Ne pas recalculer », ou déjà pointée en atelier
     * (début ou fin déclarés, attribut `has_started` chargé par l'appelant).
     * Sans date de fin, elle n'a pas de repère et se planifie normalement.
     */
    private function isFrozen(Task $task): bool
    {
        $started = (bool) ($task->getAttributes()['has_started'] ?? false);

        return ((bool) $task->not_recalculate || $started) && ! empty($task->end_date);
    }

    /**
     * Dates d'une tâche figée. L'écran tâche n'écrit que la date de fin : sans
     * début cohérent, on le déduit de la durée.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    private function frozenInterval(Task $task): array
    {
        $end = Carbon::parse($task->end_date);

        if (! empty($task->start_date)) {
            $start = Carbon::parse($task->start_date);
            if ($start->lt($end)) {
                return ['start' => $start, 'end' => $end];
            }
        }

        return ['start' => WorkingTime::subtractWorkingHours($end, $task->plannedHours()), 'end' => $end];
    }

    /**
     * Signale les liens qu'une tâche figée empêche de tenir : une passe ne peut
     * déplacer ni la tâche figée, ni, en remontant le temps, ses successeurs.
     *
     * @param  array<int, array{start: Carbon, end: Carbon}>  $placed
     */
    private function checkFrozenLinks(DependencyGraph $graph, array $placed, OrderLines $line, ?callable $delayFn, bool $isAsap): void
    {
        foreach ($graph->edges() as $edge) {
            $predecessor = $graph->task($edge->from);
            $successor   = $graph->task($edge->to);

            if (! $this->isFrozen($predecessor) && ! $this->isFrozen($successor)) {
                continue;
            }

            $from  = $placed[$edge->from];
            $to    = $placed[$edge->to];
            $shift = $this->transferHours($predecessor, $successor, $delayFn, $isAsap);
            $lag   = $this->lagHours($edge, $predecessor, $successor);

            $held = match ($edge->type) {
                TaskDependencyType::StartToStart   => $to['start']->gte($this->addHours($this->addHours($from['start'], $shift), $lag)),
                TaskDependencyType::FinishToFinish => $to['end']->gte($this->addHours($this->addHours($from['end'], $shift), $lag)),
                default                            => $to['start']->gte($this->addHours($this->addHours($from['end'], $shift), $lag)),
            };

            if (! $held) {
                $this->conflicts[$line->id][] = sprintf(
                    'OF #%s — lien %s %s %s non tenu : tâche figée',
                    $line->orders_id ?? '?',
                    $graph->label($edge->from),
                    $edge->type->value,
                    $graph->label($edge->to),
                );
            }
        }
    }

    /**
     * Ancre d'une ligne selon le sens.
     *
     * ASAP : la ligne de commande ne porte pas de date de début (c'est la
     * première tâche qui la reçoit, calculée ici) — on part donc de
     * `$asapFrom`, figé une fois par run par le job, à défaut de maintenant.
     */
    private function resolveAnchor(OrderLines $line, string $direction, ?Carbon $asapFrom = null): Carbon
    {
        if ($direction === TaskDateCalculator::DIRECTION_ASAP) {
            return $asapFrom ? $asapFrom->copy() : Carbon::now();
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
        $duration = (float) $task->plannedHours();

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
            (float) $task->plannedHours(),
            $this->taskResources($task)->pluck('label')->implode(', ') ?: 'ressource inconnue',
        );
    }
}
