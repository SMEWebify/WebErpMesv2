<?php

namespace App\Jobs;

use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\Planning\TaskResources;
use App\Models\Methods\MethodsRessources;
use App\Services\ResourceCapacityService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class CalculateTaskResources implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CACHE_KEY = 'task_calculation_resources_progress';

    private ResourceCapacityService $capacity;

    /**
     * @param bool $rebalance Reprend aussi les tâches déjà affectées automatiquement,
     *                        pour les redistribuer après un changement de capacité
     *                        (nouvelle machine, régime horaire, arrêt planifié).
     *                        Les affectations manuelles ou forcées ne sont jamais touchées,
     *                        pas plus que les tâches déjà démarrées en atelier.
     */
    public function __construct(public bool $rebalance = false)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->capacity = app(ResourceCapacityService::class);

        $this->initializeProgress();

        if ($this->rebalance) {
            $this->releaseAutomaticAssignments();
        }

        // Seules les tâches productives consomment de la capacité interne :
        // matière, fournitures et sous-traitance n'ont pas de ressource à affecter.
        $totalTasks = $this->pendingTasks()->count();

        if ($totalTasks === 0) {
            $this->markFinished();
            return;
        }

        // Build in-memory capacity map once — eliminates N+1 in remainingCapacity()
        $capacityMap = $this->buildCapacityMap();

        $processed  = 0;
        $batchMsgs  = [];

        $this->pendingTasks()
            ->with('service.Ressources.shiftPattern.slots')
            ->orderBy('id')
            ->chunkById(50, function ($tasks) use ($totalTasks, &$processed, &$capacityMap, &$batchMsgs) {
                foreach ($tasks as $task) {
                    $processed++;
                    $batchMsgs[] = $this->assignResource($task, $capacityMap);

                    // Flush cache every 10 tasks instead of every task
                    if ($processed % 10 === 0 || $processed === $totalTasks) {
                        $this->flushProgress($processed, $totalTasks, $batchMsgs);
                        $batchMsgs = [];
                    }
                }
            });

        $this->markFinished();
    }

    /** Tâches productives de commande restées sans ressource. */
    private function pendingTasks()
    {
        return Task::productive()
            ->whereNotNull('order_lines_id')
            ->whereDoesntHave('resources');
    }

    /**
     * Libère les affectations posées par l'ordonnancement, pour que la passe
     * suivante les repose sur la capacité à jour.
     *
     * Deux garde-fous : on ne touche ni aux choix humains (source manual/forced),
     * ni aux tâches sur lesquelles du temps a déjà été déclaré — déplacer une
     * tâche commencée invaliderait le réalisé déjà imputé à sa ressource.
     */
    private function releaseAutomaticAssignments(): void
    {
        $taskIds = Task::productive()
            ->whereNotNull('order_lines_id')
            ->whereHas('resources', fn ($query) => $query->where('task_resources.source', TaskResources::SOURCE_AUTO))
            ->whereDoesntHave('resources', fn ($query) => $query->whereIn('task_resources.source', [
                TaskResources::SOURCE_MANUAL,
                TaskResources::SOURCE_FORCED,
            ]))
            ->whereDoesntHave('taskActivities', fn ($query) => $query->where('type', TaskActivities::TYPE_START))
            ->pluck('tasks.id');

        TaskResources::whereIn('task_id', $taskIds)
            ->where('source', TaskResources::SOURCE_AUTO)
            ->delete();
    }

    /**
     * Load all resources with their existing task assignments into an in-memory map.
     * [resource_id => ['_model' => ressource, 'capacity' => [date => h], 'used' => [date => h]]]
     *
     * La charge existante est reconstruite avec la même répartition multi-jours que
     * celle utilisée à l'affectation : les deux calculs doivent donner le même
     * résultat, sinon la carte diverge d'une exécution à l'autre.
     */
    private function buildCapacityMap(): array
    {
        $map = [];

        // Toutes les affectations de la ressource, quel que soit le rôle : une
        // ressource a une seule nature. load_factor porte la quotité réservée
        // (0,5 h de main-d'œuvre par heure machine si un opérateur tient 2 machines).
        MethodsRessources::with(['shiftPattern.slots', 'tasks' => function ($q) {
            $q->whereNotNull('start_date')
              ->select('tasks.id', 'tasks.start_date', 'tasks.seting_time', 'tasks.unit_time', 'tasks.qty');
        }])->get()->each(function (MethodsRessources $resource) use (&$map) {
            $map[$resource->id] = ['_model' => $resource, 'capacity' => [], 'used' => []];

            foreach ($resource->tasks->sortBy('id') as $task) {
                $factor = (float) ($task->pivot->load_factor ?? 1);
                $this->book($resource, Carbon::parse($task->start_date), $task->TotalTime() * $factor, $map);
            }
        });

        return $map;
    }

    /**
     * Affecte à la tâche la capacité machine et, si elle en consomme, la
     * capacité main-d'œuvre. Met à jour la carte de capacité au fil de l'eau
     * pour que les tâches suivantes du même lot voient l'état réel.
     */
    private function assignResource(Task $task, array &$capacityMap): string
    {
        $service    = $task->service;
        $taskDate   = $task->start_date
            ? Carbon::parse($task->start_date)
            : Carbon::today();
        $taskHours  = $task->TotalTime();

        // Ressources capables de réaliser le service, par ordre de préférence.
        // Une même machine peut servir plusieurs services : le capacityMap est
        // indexé par ressource, sa charge est donc bien mutualisée entre eux.
        $candidates = $service?->Ressources ?? collect();
        $machines   = $candidates->where('is_labor', false);
        $laborPool  = $candidates->where('is_labor', true);

        $assigned = [];

        [$machine, $machinePlan] = $this->pickAvailable($machines, $taskDate, $taskHours, $capacityMap);

        if ($machine) {
            $this->attachResource($task, $machine, TaskResources::ROLE_MACHINE, 1.0, $machinePlan, $capacityMap);
            $assigned[] = $this->describe($machine, $machinePlan);
        }

        // Quotité de main-d'œuvre : portée par la machine retenue, ou 1 quand le
        // service est un poste purement manuel (aucune machine déclarée).
        // Si des machines existent mais qu'aucune n'est libre, on ne réserve pas
        // d'opérateur : il n'y aurait rien à conduire.
        $laborFactor = $machine
            ? (float) $machine->labor_ratio
            : ($machines->isEmpty() ? 1.0 : 0.0);

        if ($laborFactor > 0 && $laborPool->isNotEmpty()) {
            [$labor, $laborPlan] = $this->pickAvailable($laborPool, $taskDate, $taskHours * $laborFactor, $capacityMap);

            if ($labor) {
                $this->attachResource($task, $labor, TaskResources::ROLE_LABOR, $laborFactor, $laborPlan, $capacityMap);
                $assigned[] = $this->describe($labor, $laborPlan);
            }
        }

        if ($assigned !== []) {
            return implode(' + ', $assigned) . ' affecté(e) à la tâche #' . $task->id . ' (' . ($service?->label ?? 'N/A') . ')';
        }

        return $this->explainFailure($task, $service, $taskDate, $candidates, $machines, $laborPool, $machine);
    }

    /**
     * Un simple « aucune ressource disponible » n'indique pas où regarder :
     * service sans ressource, jour fermé, capacité saturée, ou machine occupée
     * qui bloque la réservation d'un opérateur sont quatre causes distinctes.
     */
    private function explainFailure(
        Task $task,
        $service,
        Carbon $date,
        $candidates,
        $machines,
        $laborPool,
        ?MethodsRessources $machine
    ): string {
        $suffix = ' — tâche #' . $task->id . ' (' . ($service?->label ?? 'N/A') . ')';
        $day = $date->toDateString();

        if ($candidates->isEmpty()) {
            return 'Aucune ressource rattachée à ce service' . $suffix;
        }

        if ($machines->isNotEmpty() && ! $machine) {
            return 'Aucune machine ouverte ou libre le ' . $day . ' parmi ' . $machines->pluck('label')->implode(', ') . $suffix;
        }

        if ($laborPool->isEmpty()) {
            return 'Aucune main-d\'œuvre rattachée à ce service, et pas de machine disponible le ' . $day . $suffix;
        }

        return 'Aucune capacité ouverte ou libre le ' . $day . ' parmi ' . $candidates->pluck('label')->implode(', ') . $suffix;
    }

    /**
     * Première ressource du lot capable d'absorber la charge à partir de cette
     * date, en la répartissant sur les jours ouverts successifs.
     *
     * @return array{0: ?MethodsRessources, 1: array<string, float>}
     */
    private function pickAvailable($pool, Carbon $date, float $hours, array &$capacityMap): array
    {
        foreach ($pool as $resource) {
            $plan = $this->plan($resource, $date, $hours, $capacityMap);

            if ($plan !== null) {
                return [$resource, $plan];
            }
        }

        return [null, []];
    }

    /**
     * Répartition des heures sur les jours ouverts de la ressource, ou null si
     * la charge ne tient pas dans l'horizon planifiable.
     *
     * @return array<string, float>|null
     */
    private function plan(MethodsRessources $resource, Carbon $date, float $hours, array &$capacityMap): ?array
    {
        $residual = function (string $day) use ($resource, &$capacityMap): float {
            $available = $this->availableHours($resource, $day, $capacityMap);
            $used = $capacityMap[$resource->id]['used'][$day] ?? 0.0;

            return $available - $used;
        };

        return $this->capacity->spreadHours($hours, $date, $residual);
    }

    /** Capacité disponible du jour, mémorisée par ressource et par date. */
    private function availableHours(MethodsRessources $resource, string $date, array &$capacityMap): float
    {
        if (! isset($capacityMap[$resource->id])) {
            $capacityMap[$resource->id] = ['_model' => $resource, 'capacity' => [], 'used' => []];
        }

        if (! array_key_exists($date, $capacityMap[$resource->id]['capacity'])) {
            $model = $capacityMap[$resource->id]['_model'] ?? $resource;
            $capacityMap[$resource->id]['capacity'][$date] = $this->capacity->availableHours($model, Carbon::parse($date));
        }

        return $capacityMap[$resource->id]['capacity'][$date];
    }

    /**
     * Consomme les heures dans la carte. Une charge qui ne tient pas dans
     * l'horizon (reconstruction d'affectations manuelles, capacité réduite
     * depuis) est imputée au premier jour : la surcharge est un fait, la perdre
     * fausserait la carte.
     */
    private function book(MethodsRessources $resource, Carbon $date, float $hours, array &$capacityMap): array
    {
        $plan = $this->plan($resource, $date, $hours, $capacityMap) ?? [$date->toDateString() => $hours];

        foreach ($plan as $day => $booked) {
            $capacityMap[$resource->id]['used'][$day] = ($capacityMap[$resource->id]['used'][$day] ?? 0.0) + $booked;
        }

        return $plan;
    }

    private function attachResource(
        Task $task,
        MethodsRessources $resource,
        string $role,
        float $loadFactor,
        array $plan,
        array &$capacityMap
    ): void {
        $task->resources()->attach($resource->id, [
            'role'        => $role,
            'source'      => TaskResources::SOURCE_AUTO,
            'load_factor' => $loadFactor,
        ]);

        // Update in-memory map so subsequent tasks in the same chunk see correct capacity
        foreach ($plan as $day => $booked) {
            $capacityMap[$resource->id]['used'][$day] = ($capacityMap[$resource->id]['used'][$day] ?? 0.0) + $booked;
        }
    }

    /** « Laser Trumpf » ou « Laser Trumpf (3 j) » quand la charge déborde du jour. */
    private function describe(MethodsRessources $resource, array $plan): string
    {
        $days = count($plan);

        return $days > 1 ? $resource->label . ' (' . $days . ' j)' : $resource->label;
    }

    private function initializeProgress(): void
    {
        Cache::put(self::CACHE_KEY, [
            'status'   => 'running',
            'progress' => 0,
            'count'    => 0,
            'messages' => [],
        ], now()->addHour());
    }

    private function flushProgress(int $processed, int $total, array $newMessages): void
    {
        $state    = Cache::get(self::CACHE_KEY, []);
        $messages = array_slice(array_merge($state['messages'] ?? [], $newMessages), -20);

        Cache::put(self::CACHE_KEY, [
            'status'   => 'running',
            'progress' => round(($processed / $total) * 100, 2),
            'count'    => $processed,
            'messages' => $messages,
        ], now()->addHour());
    }

    private function markFinished(): void
    {
        $state = Cache::get(self::CACHE_KEY, []);

        Cache::put(self::CACHE_KEY, [
            'status'   => 'finished',
            'progress' => $state['progress'] ?? 100,
            'count'    => $state['count'] ?? 0,
            'messages' => $state['messages'] ?? [],
        ], now()->addHour());
    }
}
