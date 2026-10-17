<?php

namespace Tests\Unit\Planning;

use App\Models\Methods\MethodsRessources;
use App\Models\Planning\Task;
use App\Models\Workflow\OrderLines;
use App\Services\Planning\FiniteCapacityScheduler;
use App\Services\ResourceCapacityService;
use App\Services\TaskDateCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Jeux d'essai de l'ordonnancement, sans base : lignes et tâches en mémoire,
 * capacité simulée, délais inter-opérations simulés.
 *
 * Partagés par la référence figée (dates calculées AVANT l'introduction des
 * dépendances entre opérations) et par le test d'équivalence du moteur à
 * dépendances : une gamme sans lien doit donner exactement ces dates.
 *
 * Calendrier : sans régime horaire ni férié en base, WorkingTime retombe sur
 * lundi–vendredi 8 h–18 h.
 */
final class PlanningScenarios
{
    /**
     * Une tâche : [id, ordre, réglage, unitaire, quantité, service, [ressource => quotité]].
     *
     * @return array<string, array{direction: string, mode: string, delays: bool, capacity: array<string, float>, lines: array}>
     */
    public static function all(): array
    {
        $fractions = [
            [10, 10, 0.25, 0, 1, null, []],
            [11, 20, 0, 0.75, 1, null, []],
            [12, 30, 0.5, 0.4, 5, null, []],
            [13, 40, 1.5, 0, 1, null, []],
        ];

        $services = [
            [20, 10, 1, 0.5, 2, 1, []],
            [21, 20, 0.5, 0.25, 4, 2, []],
            [22, 30, 0, 1.5, 1, 3, []],
            [23, 40, 1, 0, 1, null, []],
            [24, 50, 2, 0, 1, 2, []],
        ];

        $atelier = [
            ['id' => 1, 'order' => 100, 'anchor' => '2024-05-10 18:00:00', 'tasks' => [
                [30, 10, 1, 0.5, 4, 1, [1 => 1.0]],
                [31, 20, 0.5, 1, 3, 2, [2 => 1.0, 9 => 0.5]],
                [32, 30, 2, 0, 1, 3, [1 => 1.0]],
            ]],
            ['id' => 2, 'order' => 200, 'anchor' => '2024-05-09 18:00:00', 'tasks' => [
                [40, 10, 0, 1, 5, 1, [1 => 1.0]],
                [41, 20, 1, 0.75, 4, 2, [2 => 1.0]],
            ]],
            ['id' => 3, 'order' => 300, 'anchor' => '2024-05-10 18:00:00', 'tasks' => [
                [50, 10, 0.5, 0.25, 6, 1, [1 => 1.0]],
                [51, 20, 3, 0, 1, 2, [2 => 1.0, 9 => 0.5]],
                [52, 30, 0, 0.5, 3, null, []],
            ]],
        ];

        $atelierAsap = array_map(function (array $line, string $anchor) {
            $line['anchor'] = $anchor;

            return $line;
        }, $atelier, ['2024-05-06 08:00:00', '2024-05-06 10:00:00', '2024-05-06 08:00:00']);

        return [
            'alap_infini_entier' => [
                'direction' => 'alap', 'mode' => 'infinite', 'delays' => false, 'capacity' => [],
                'lines' => [['id' => 1, 'order' => 100, 'anchor' => '2024-05-10 00:00:00', 'tasks' => [
                    [1, 10, 1, 0.5, 4, null, []],
                    [2, 20, 0, 2, 3, null, []],
                    [3, 30, 2, 0, 1, null, []],
                ]]],
            ],
            'alap_infini_fractions' => [
                'direction' => 'alap', 'mode' => 'infinite', 'delays' => false, 'capacity' => [],
                'lines' => [['id' => 1, 'order' => 100, 'anchor' => '2024-05-10 12:00:00', 'tasks' => $fractions]],
            ],
            'asap_infini_fractions' => [
                'direction' => 'asap', 'mode' => 'infinite', 'delays' => false, 'capacity' => [],
                'lines' => [['id' => 1, 'order' => 100, 'anchor' => '2024-05-10 16:30:00', 'tasks' => $fractions]],
            ],
            'alap_infini_delais' => [
                'direction' => 'alap', 'mode' => 'infinite', 'delays' => true, 'capacity' => [],
                'lines' => [['id' => 1, 'order' => 100, 'anchor' => '2024-05-17 00:00:00', 'tasks' => $services]],
            ],
            'asap_infini_delais' => [
                'direction' => 'asap', 'mode' => 'infinite', 'delays' => true, 'capacity' => [],
                'lines' => [['id' => 1, 'order' => 100, 'anchor' => '2024-05-06 08:00:00', 'tasks' => $services]],
            ],
            'asap_samedi_delais' => [
                'direction' => 'asap', 'mode' => 'infinite', 'delays' => true, 'capacity' => [],
                'lines' => [['id' => 1, 'order' => 100, 'anchor' => '2024-05-11 09:00:00', 'tasks' => $services]],
            ],
            'alap_fini_concurrence' => [
                'direction' => 'alap', 'mode' => 'finite', 'delays' => false,
                'capacity' => ['default' => 8.0, '2024-05-08' => 4.0],
                'lines' => $atelier,
            ],
            'asap_fini_concurrence' => [
                'direction' => 'asap', 'mode' => 'finite', 'delays' => false,
                'capacity' => ['default' => 8.0, '2024-05-08' => 4.0],
                'lines' => $atelierAsap,
            ],
            'alap_fini_delais' => [
                'direction' => 'alap', 'mode' => 'finite', 'delays' => true,
                'capacity' => ['default' => 6.0],
                'lines' => $atelier,
            ],
            'asap_fini_delais' => [
                'direction' => 'asap', 'mode' => 'finite', 'delays' => true,
                'capacity' => ['default' => 6.0],
                'lines' => $atelierAsap,
            ],
            'alap_fini_debordement' => [
                'direction' => 'alap', 'mode' => 'finite', 'delays' => false,
                'capacity' => ['default' => 2.0],
                'lines' => [['id' => 1, 'order' => 100, 'anchor' => '2024-05-10 18:00:00', 'tasks' => [
                    [60, 10, 0, 12, 1, 1, [1 => 1.0]],
                    [61, 20, 1, 0, 1, 2, [2 => 1.0]],
                ]]],
            ],
        ];
    }

    /**
     * Délai inter-opérations simulé, mêmes règles que InterOperationDelayResolver :
     * transfert par paire, sinon défaut, plus un écart minimum toujours ajouté ;
     * transfert nul si un des deux services est inconnu.
     */
    public static function delayFn(): callable
    {
        return function (?int $from, ?int $to): float {
            $minGap = 0.5;
            if ($from === null || $to === null) {
                return $minGap;
            }

            $pairs = ['1|2' => 2.5, '2|3' => 0.75];

            return ($pairs[$from . '|' . $to] ?? 1.0) + $minGap;
        };
    }

    public static function scheduler(array $capacityByDate): FiniteCapacityScheduler
    {
        $capacity = new class($capacityByDate) extends ResourceCapacityService {
            public function __construct(private array $byDate) {}

            public function availableHours(MethodsRessources $resource, Carbon $date): float
            {
                return (float) ($this->byDate[$date->toDateString()] ?? $this->byDate['default'] ?? 8.0);
            }
        };

        return new FiniteCapacityScheduler(new TaskDateCalculator(), $capacity);
    }

    /** @return OrderLines[] */
    public static function lines(array $scenario): array
    {
        $lines = [];

        foreach ($scenario['lines'] as $def) {
            $line = new OrderLines();
            $line->id = $def['id'];
            $line->orders_id = $def['order'];
            // Ancre ALAP ; en ASAP, la même date sert de départ du calcul (run()).
            $line->internal_delay = $def['anchor'];
            $line->exists = true;

            $tasks = array_map(fn (array $t) => self::task(...$t), $def['tasks']);
            $line->setRelation('Task', Collection::make($tasks));
            $lines[] = $line;
        }

        return $lines;
    }

    public static function task(int $id, int $ordre, float $setup, float $unit, float $qty, ?int $serviceId, array $resources): Task
    {
        $task = new Task();
        $task->id = $id;
        $task->label = 'T' . $id;
        $task->ordre = $ordre;
        $task->seting_time = $setup;
        $task->unit_time = $unit;
        // Sans ligne rattachée, la quantité de la tâche sert de quantité de lot.
        $task->qty = $qty;
        $task->methods_services_id = $serviceId;
        $task->type = 1;
        $task->not_recalculate = 0;
        $task->exists = true;

        $collection = new EloquentCollection();
        foreach ($resources as $resourceId => $loadFactor) {
            $resource = new MethodsRessources();
            $resource->id = $resourceId;
            $resource->label = 'R' . $resourceId;
            $resource->is_labor = $resourceId >= 9;
            $resource->exists = true;
            $resource->pivot = (object) ['load_factor' => $loadFactor];
            $collection->push($resource);
        }
        $task->setRelation('resources', $collection);
        $task->setRelation('predecessorLinks', new EloquentCollection());

        return $task;
    }

    /**
     * Joue un scénario et rend les dates par « ligne:tâche », triées, plus les
     * tâches débordées. $chain remplace l'appel historique pour comparer un
     * autre chemin de calcul sur les mêmes données.
     *
     * @return array{dates: array<string, array{0: string, 1: string}>, unfitted: string[]}
     */
    public static function run(array $scenario, ?callable $chain = null): array
    {
        $scheduler = self::scheduler($scenario['capacity']);
        $delayFn = $scenario['delays'] ? self::delayFn() : null;
        $dates = [];

        foreach (self::lines($scenario) as $line) {
            // ASAP : la ligne n'a pas de date de début, le départ est fixé par
            // l'appelant (le job fige « maintenant » pour tout le run).
            $asapFrom = Carbon::parse($line->internal_delay);

            $result = $chain
                ? $chain($scheduler, $line, $scenario['direction'], $scenario['mode'], $delayFn, $asapFrom)
                : $scheduler->chainOrderLine($line, $scenario['direction'], $scenario['mode'], $delayFn, $asapFrom);

            foreach ($result as $entry) {
                $dates[$line->id . ':' . $entry['task']->id] = [
                    $entry['start']->format('Y-m-d H:i:s'),
                    $entry['end']->format('Y-m-d H:i:s'),
                ];
            }
        }

        ksort($dates, SORT_NATURAL);

        return ['dates' => $dates, 'unfitted' => $scheduler->unfittedMessages()];
    }
}
