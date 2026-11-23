<?php

namespace App\Services\Integrations\FactoryTwin;

use App\Enums\EquipmentKind;
use App\Models\Methods\MethodsRessources;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\Planning\TaskResources;
use App\Services\ResourceCapacityService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Données du jumeau numérique d'atelier, au format du contrat
 * « factory-twin.snapshot/1 » / « factory-twin.order/1 » du package factory-twin.
 *
 * Ne dépend pas du package : renvoie des tableaux, l'adaptateur
 * ErpTwinDataProvider ne fait que les transmettre. Lecture seule.
 *
 * - Opération = poste de charge productif (methods_services type 1), code en majuscules
 *   (le jumeau rapproche les postes de son plan par ce code).
 * - Machine = ressource non main-d'œuvre du poste ; une tâche y est rangée par sa
 *   ressource « machine » (task_resources).
 * - `kind` (modèle 3D) = App\Enums\EquipmentKind de la ressource, sinon du poste.
 * - Tâches = opérations productives des lignes article de commandes ouvertes (statu 1-3).
 * - Flux = pièces qui attendent devant une opération parce que l'opération précédente
 *   de la même ligne (ordre de gamme) est finie ou a déclaré des bonnes ; vers
 *   l'expédition quand toutes les opérations de la ligne sont finies et qu'il reste à livrer.
 * - Charge du jour : capacité disponible (ResourceCapacityService), temps pointé
 *   aujourd'hui, et reste à faire des tâches en cours ou prévues jusqu'à ce soir.
 *
 * Nombre de requêtes indépendant du nombre de tâches (plus la capacité par machine).
 */
class FactoryTwinSnapshot
{
    public const FINISHED = ['Finished', 'Supplied'];

    public const IN_PROGRESS = ['Started', 'In progress'];

    /** Commandes dont l'atelier s'occupe encore : ouverte, en cours, livrée en partie. */
    public const OPEN_ORDER_STATUSES = [1, 2, 3];

    /** Tâches listées par file, au plus (le compteur « open » reste le vrai total). */
    public const QUEUE_LIMIT = 30;

    public function __construct(private readonly ResourceCapacityService $capacity)
    {
    }

    public function snapshot(): array
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $tonight = $now->copy()->endOfDay();

        $services = MethodsServices::query()
            ->where('type', MethodsServices::TYPE_PRODUCTIVE)
            ->orderBy('ordre')->orderBy('id')
            ->get(['id', 'code', 'label', 'color', 'equipment_kind']);
        $opCode = $services->mapWithKeys(fn ($s) => [$s->id => self::opCode($s)]);

        $machines = MethodsRessources::query()
            ->with('shiftPattern.slots')
            ->whereIn('methods_services_id', $services->pluck('id'))
            ->where(fn ($q) => $q->where('is_labor', false)->orWhereNull('is_labor'))
            ->orderBy('ordre')->orderBy('id')
            ->get();
        $laborCapacity = MethodsRessources::query()
            ->with('shiftPattern.slots')
            ->whereIn('methods_services_id', $services->pluck('id'))
            ->where('is_labor', true)
            ->get();

        $tasks = $this->tasks($today);
        $activities = $this->activities($tasks->pluck('id'));

        // Temps pointé : depuis toujours (reste à faire) et depuis minuit (fait aujourd'hui).
        foreach ($tasks as $task) {
            $acts = $activities->get($task->id, collect());
            $task->worked_seconds = Task::workedSecondsFrom($acts);
            $task->today_seconds = Task::workedSecondsFrom($acts->map(fn ($a) => (object) [
                'type' => $a->type,
                'timestamp' => max(Carbon::parse($a->timestamp), $today),
                'user_id' => $a->user_id,
                'methods_ressources_id' => $a->methods_ressources_id,
            ]));
            $task->good_qty = (float) $acts->where('type', TaskActivities::TYPE_DECLARE_GOOD)->sum('good_qt');
            $task->finished_at = $acts->where('type', TaskActivities::TYPE_FINISH)->max('timestamp');
            $task->last_good_at = $acts->where('type', TaskActivities::TYPE_DECLARE_GOOD)->max('timestamp');
            $task->op = $opCode[$task->methods_services_id] ?? null;
            $task->state = $this->state($task);
            $task->planned_min = round($this->plannedHours($task) * 60);
            $task->remaining_min = max(0, $task->planned_min - round($task->worked_seconds / 60));
            $task->due_today = $task->state === 'in_progress'
                || ($task->start_date && Carbon::parse($task->start_date)->lte($tonight))
                || ($task->end_date && Carbon::parse($task->end_date)->lte($tonight));
            $task->is_late = $this->isLate($task, $now, $today);
        }

        $open = $tasks->filter(fn ($t) => $t->state !== 'done' && $t->in_open_order);

        $operations = $services->map(function ($service) use ($opCode, $machines, $laborCapacity, $tasks, $open, $today) {
            $code = $opCode[$service->id];
            $own = $machines->where('methods_services_id', $service->id);
            $capacityFrom = $own->isNotEmpty() ? $own : $laborCapacity->where('methods_services_id', $service->id);
            $capacity = round($capacityFrom->sum(fn ($r) => $this->capacity->availableHours($r, $today)) * 60);

            return $this->workload(['code' => $code, 'color' => $service->color, 'kind' => EquipmentKind::tryFrom((string) $service->equipment_kind)?->value], $capacity,
                $tasks->where('op', $code), $open->where('op', $code));
        })->values();

        $resources = $machines->map(function ($machine) use ($opCode, $services, $tasks, $open, $today) {
            $service = $services->firstWhere('id', $machine->methods_services_id);
            $capacity = round($this->capacity->availableHours($machine, $today) * 60);

            return $this->workload([
                'code' => (string) $machine->code,
                'name' => $machine->label,
                'type' => $service?->label,
                'operation_code' => $opCode[$machine->methods_services_id] ?? null,
                'brand' => null,
                'color' => $machine->color,
                // Modèle 3D : type de la ressource, sinon du poste ; null = deviné par le nom.
                'kind' => EquipmentKind::resolve($machine->equipment_kind, $service?->equipment_kind)?->value,
            ], $capacity, $tasks->where('machine_id', $machine->id), $open->where('machine_id', $machine->id));
        })->values();

        $machineCode = $machines->pluck('code', 'id');

        return [
            'contract' => 'factory-twin.snapshot/1',
            'operations' => $operations->all(),
            'resources' => $resources->all(),
            'queues' => [
                'operations' => $operations->mapWithKeys(fn ($o) => [$o['code'] => $this->queue($open->where('op', $o['code']))])->all(),
                'resources' => $machines->mapWithKeys(fn ($m) => [(string) $m->code => $this->queue($open->where('machine_id', $m->id))])->all(),
            ],
            'flows' => $this->flows($tasks->filter(fn ($t) => $t->in_open_order), $machineCode, $now, $today),
        ];
    }

    public function orderTrace(string $ref): array
    {
        $ref = trim($ref);
        $order = $ref === '' ? null : DB::table('orders')
            ->leftJoin('companies', 'companies.id', '=', 'orders.companies_id')
            ->whereRaw('LOWER(orders.code) = ?', [mb_strtolower($ref)])
            ->first(['orders.id', 'orders.code', 'companies.label as customer_name']);

        if (! $order) {
            return ['ref' => $ref, 'found' => false, 'operations' => [], 'route' => []];
        }

        $tasks = $this->baseTaskQuery()
            ->where('orders.id', $order->id)
            ->get()
            ->each(function ($t) {
                $t->op = self::opCode((object) ['id' => $t->methods_services_id, 'code' => $t->service_code]);
                $t->state = $this->state($t);
            });

        $operations = [];
        $route = $tasks->sortBy([['line_ordre', 'asc'], ['ordre', 'asc']])
            ->groupBy('op')
            ->sortBy(fn ($group) => $group->min('ordre'))
            ->map(function ($group, $code) use (&$operations) {
                $done = $group->where('state', 'done')->count();
                $operations[$code] = [
                    'status' => $done === $group->count() ? 'done'
                        : ($done > 0 || $group->contains('state', 'in_progress') ? 'in_progress' : 'planned'),
                    'progress_percent' => (int) round($done / $group->count() * 100),
                    'tasks_done' => $done,
                    'tasks_total' => $group->count(),
                ];

                return $code;
            })->values()->all();

        $lines = DB::table('order_lines')->where('orders_id', $order->id)
            ->where(fn ($q) => $q->where('line_type', 'article')->orWhereNull('line_type'))
            ->get(['delivery_date', 'delivered_remaining_qty']);
        $due = $lines->where('delivered_remaining_qty', '>', 0)->min('delivery_date') ?? $lines->max('delivery_date');

        return [
            'ref' => $order->code,
            'found' => true,
            'customer_name' => $order->customer_name,
            'jobs_count' => $tasks->pluck('order_lines_id')->unique()->count(),
            'overall_progress' => $tasks->isEmpty() ? 0 : (int) round($tasks->where('state', 'done')->count() / $tasks->count() * 100),
            'due_date' => $due ? Carbon::parse($due)->toDateString() : null,
            'operations' => $operations,
            'route' => $route,
        ];
    }

    /** Code d'opération vu par le jumeau : code du poste en majuscules, sinon son id. */
    public static function opCode(object $service): string
    {
        $code = trim((string) ($service->code ?? ''));

        return mb_strtoupper($code !== '' ? $code : 'OP' . $service->id);
    }

    /**
     * Opérations productives des commandes ouvertes, plus celles pointées
     * aujourd'hui (une tâche finie ce matin compte dans le « fait » du jour).
     */
    private function tasks(Carbon $today): Collection
    {
        $touchedToday = DB::table('task_activities')
            ->where('timestamp', '>=', $today)
            ->whereIn('type', [TaskActivities::TYPE_START, TaskActivities::TYPE_END, TaskActivities::TYPE_FINISH])
            ->distinct()->pluck('task_id');

        return $this->baseTaskQuery()
            ->where(fn ($q) => $q->whereIn('orders.statu', self::OPEN_ORDER_STATUSES)->orWhereIn('tasks.id', $touchedToday))
            ->get()
            ->each(fn ($t) => $t->in_open_order = in_array((int) $t->order_statu, self::OPEN_ORDER_STATUSES, true));
    }

    private function baseTaskQuery()
    {
        return DB::table('tasks')
            ->join('methods_services', 'methods_services.id', '=', 'tasks.methods_services_id')
            ->join('order_lines', 'order_lines.id', '=', 'tasks.order_lines_id')
            ->join('orders', 'orders.id', '=', 'order_lines.orders_id')
            ->leftJoin('statuses', 'statuses.id', '=', 'tasks.status_id')
            ->leftJoin('companies', 'companies.id', '=', 'orders.companies_id')
            ->leftJoin('task_resources', function ($join) {
                $join->on('task_resources.task_id', '=', 'tasks.id')
                    ->where('task_resources.role', TaskResources::ROLE_MACHINE);
            })
            ->where('methods_services.type', MethodsServices::TYPE_PRODUCTIVE)
            ->where(fn ($q) => $q->where('order_lines.line_type', 'article')->orWhereNull('order_lines.line_type'))
            ->select([
                'tasks.id', 'tasks.code', 'tasks.label', 'tasks.ordre', 'tasks.order_lines_id', 'tasks.methods_services_id',
                'tasks.seting_time', 'tasks.unit_time', 'tasks.planned_qty', 'tasks.start_date', 'tasks.end_date',
                'tasks.material', 'tasks.thickness', 'tasks.x_size', 'tasks.y_size', 'tasks.weight',
                'methods_services.code as service_code',
                'statuses.title as status_title',
                'task_resources.methods_ressources_id as machine_id',
                'order_lines.code as line_code', 'order_lines.label as line_label', 'order_lines.ordre as line_ordre',
                'order_lines.qty as line_qty', 'order_lines.delivery_date', 'order_lines.delivered_remaining_qty',
                'orders.code as order_code', 'orders.statu as order_statu',
                'companies.label as customer_name',
            ]);
    }

    /** @return Collection<int, Collection> activités utiles au jumeau, par tâche */
    private function activities(Collection $taskIds): Collection
    {
        if ($taskIds->isEmpty()) {
            return collect();
        }

        return $taskIds->chunk(1000)->flatMap(fn ($ids) => DB::table('task_activities')
            ->whereIn('task_id', $ids->all())
            ->whereIn('type', [
                TaskActivities::TYPE_START, TaskActivities::TYPE_END, TaskActivities::TYPE_FINISH, TaskActivities::TYPE_DECLARE_GOOD,
            ])
            ->orderBy('timestamp')->orderBy('id')
            ->get(['task_id', 'type', 'timestamp', 'user_id', 'methods_ressources_id', 'good_qt']))
            ->groupBy('task_id');
    }

    private function state(object $task): string
    {
        if (in_array($task->status_title, self::FINISHED, true)) {
            return 'done';
        }

        return in_array($task->status_title, self::IN_PROGRESS, true) ? 'in_progress' : 'planned';
    }

    /** Même calcul que Task::TotalTime() : réglage + temps unitaire × quantité de l'opération. */
    private function plannedHours(object $task): float
    {
        $qty = $task->planned_qty !== null && $task->planned_qty !== '' ? (float) $task->planned_qty : (float) $task->line_qty;

        return (float) $task->seting_time + (float) $task->unit_time * $qty;
    }

    private function requiredQty(object $task): float
    {
        return $task->planned_qty !== null && $task->planned_qty !== '' ? (float) $task->planned_qty : (float) $task->line_qty;
    }

    private function isLate(object $task, Carbon $now, Carbon $today): bool
    {
        if ($task->state === 'done') {
            return false;
        }

        return ($task->end_date && Carbon::parse($task->end_date)->lt($now))
            || ($task->delivery_date && Carbon::parse($task->delivery_date)->lt($today));
    }

    private function workload(array $base, float $capacity, Collection $all, Collection $open): array
    {
        $done = round($all->sum('today_seconds') / 60);
        $remaining = $open->filter(fn ($t) => $t->due_today)->sum('remaining_min');

        return $base + [
            'capacity_min' => $capacity,
            'done_min' => $done,
            'remaining_min' => $remaining,
            'usage_percent' => $capacity > 0 ? (int) round(($done + $remaining) / $capacity * 100) : null,
        ];
    }

    private function queue(Collection $open): array
    {
        $sorted = $open->sortBy([
            fn ($a, $b) => ($b->state === 'in_progress') <=> ($a->state === 'in_progress'),
            fn ($a, $b) => $b->is_late <=> $a->is_late,
            fn ($a, $b) => strcmp((string) ($a->end_date ?? $a->delivery_date ?? '9999'), (string) ($b->end_date ?? $b->delivery_date ?? '9999')),
        ]);

        return [
            'in_progress' => $open->where('state', 'in_progress')->count(),
            'open' => $open->count(),
            'late' => $open->where('is_late', true)->count(),
            'tasks' => $sorted->take(self::QUEUE_LIMIT)->map(fn ($t) => [
                'id' => $t->id,
                'status' => $t->state,
                'of_code' => self::ofCode($t),
                'order_ref' => $t->order_code,
                'customer_name' => $t->customer_name,
                'label' => $t->line_label ?: $t->label,
                'required_qty' => $this->requiredQty($t),
                'produced_qty' => $t->good_qty,
                'planned_time_min' => $t->planned_min,
                'planned_end_at' => $t->end_date ? Carbon::parse($t->end_date)->toIso8601String() : null,
                'is_late' => $t->is_late,
                'part' => $this->part($t),
            ])->values()->all(),
        ];
    }

    /**
     * Pièces en attente devant une opération : l'opération précédente de la ligne
     * est finie (toute la quantité) ou a déclaré des bonnes (en cours).
     */
    private function flows(Collection $tasks, Collection $machineCode, Carbon $now, Carbon $today): array
    {
        $flows = [];
        $add = function (array $from, array $to, object $job, float $qty, float $minutes, ?string $since, string $upstream) use (&$flows, $now, $today) {
            $end = fn ($e) => isset($e['area']) ? 'a:' . $e['area'] : (isset($e['resource']) ? 'r:' . $e['resource'] : 'o:' . $e['operation']);
            $key = $end($from) . '>' . $end($to);
            $wait = $since ? max(0, (int) Carbon::parse($since)->diffInMinutes($now)) : null;
            $late = $job->delivery_date && Carbon::parse($job->delivery_date)->lt($today);

            $flows[$key] ??= ['key' => $key, 'from' => $from, 'to' => $to, 'qty' => 0, 'minutes' => 0, 'late' => 0, 'max_wait_min' => null, 'jobs' => []];
            $flows[$key]['qty'] += $qty;
            $flows[$key]['minutes'] += $minutes;
            $flows[$key]['late'] += $late ? 1 : 0;
            $flows[$key]['max_wait_min'] = $wait === null ? $flows[$key]['max_wait_min'] : max($flows[$key]['max_wait_min'] ?? 0, $wait);
            $flows[$key]['jobs'][] = [
                'of_code' => self::ofCode($job),
                'order_ref' => $job->order_code,
                'label' => $job->line_label ?: $job->label,
                'qty' => $qty,
                'minutes' => $minutes,
                'due_date' => $job->delivery_date ? Carbon::parse($job->delivery_date)->toDateString() : null,
                'waiting_since' => $since ? Carbon::parse($since)->toIso8601String() : null,
                'wait_min' => $wait,
                'upstream_status' => $upstream,
                'part' => $this->part($job),
            ];
        };
        $end = fn ($t) => ['resource' => isset($machineCode[$t->machine_id]) ? (string) $machineCode[$t->machine_id] : null, 'operation' => $t->op];

        foreach ($tasks->groupBy('order_lines_id') as $line) {
            $route = $line->sortBy([['ordre', 'asc'], ['id', 'asc']])->values();

            foreach ($route as $i => $task) {
                if ($i === 0 || $task->state !== 'planned') {
                    continue;
                }
                $previous = $route[$i - 1];
                if ($previous->state === 'done') {
                    $add($end($previous), $end($task), $task, $this->requiredQty($task), $task->remaining_min, $previous->finished_at, 'done');
                } elseif ($previous->state === 'in_progress' && $previous->good_qty > 0) {
                    $required = $this->requiredQty($task);
                    $qty = min($previous->good_qty, $required);
                    $minutes = $required > 0 ? round($task->remaining_min * $qty / $required) : 0;
                    $add($end($previous), $end($task), $task, $qty, $minutes, $previous->last_good_at, 'in_progress');
                }
            }

            $last = $route->last();
            if ($last && $route->every(fn ($t) => $t->state === 'done') && (float) $last->delivered_remaining_qty > 0) {
                $add($end($last), ['resource' => null, 'operation' => null, 'area' => 'shipping'], $last,
                    (float) $last->delivered_remaining_qty, 0, $last->finished_at, 'done');
            }
        }

        return array_values($flows);
    }

    /** OF au sens du jumeau = la ligne de commande, comme dans le contrat Nest2Prod (OF<id de ligne>). */
    private static function ofCode(object $task): string
    {
        return 'OF' . $task->order_lines_id;
    }

    private function part(object $task): ?array
    {
        $part = array_filter([
            'material' => $task->material ?: null,
            'thickness' => $task->thickness !== null ? (float) $task->thickness : null,
            'dim_x' => $task->x_size !== null ? (float) $task->x_size : null,
            'dim_y' => $task->y_size !== null ? (float) $task->y_size : null,
            'weight' => $task->weight !== null ? (float) $task->weight : null,
        ], fn ($v) => $v !== null && $v !== 0.0);

        return $part === [] ? null : $part + ['dxf_url' => null, 'image_url' => null];
    }
}
