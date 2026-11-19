<?php

namespace Database\Seeders;

use App\Models\Methods\MethodsRessources;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Planning\InterOperationDelayResolver;
use App\Services\StockReservationService;
use App\Services\TaskDateCalculator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CreateTaskSeeder extends Seeder
{
    /** Statuts de tâche attendus par l'application (titres cherchés tels quels). */
    private const STATUSES = ['Open', 'Started', 'In progress', 'Finished', 'Suspended', 'To RFQ', 'RFQ in progress', 'Outsourced', 'Supplied'];

    /**
     * Gammes types (codes de postes des trames de devis), avec leur poids et la
     * matière consommée. Un poste absent de la base est sauté.
     */
    private const ROUTINGS = [
        ['weight' => 25, 'ops' => ['LAS', 'EBAV'], 'material' => 'MAT'],
        ['weight' => 25, 'ops' => ['LAS', 'BEND'], 'material' => 'MAT'],
        ['weight' => 12, 'ops' => ['LAS', 'BEND', 'PAINT'], 'material' => 'MAT'],
        ['weight' => 10, 'ops' => ['CAO', 'LAS', 'BEND', 'WELD', 'EBAV'], 'material' => 'MAT', 'supplies' => true],
        ['weight' => 5, 'ops' => ['CAO', 'LAS', 'BEND', 'WELD', 'CTRL', 'GALVA'], 'material' => 'MAT', 'supplies' => true],
        ['weight' => 5, 'ops' => ['CAO', 'LAS', 'ROUL', 'WELD', 'CTRL'], 'material' => 'MAT', 'supplies' => true],
        ['weight' => 6, 'ops' => ['SCIE', 'WELD', 'EBAV', 'PAINT'], 'material' => 'PROF'],
        ['weight' => 6, 'ops' => ['SCIE', 'TOUR', 'CTRL'], 'material' => 'BLOC'],
        ['weight' => 6, 'ops' => ['SCIE', 'FRAI', 'EBAV', 'CTRL'], 'material' => 'BLOC'],
    ];

    /** Temps de base en heures : [réglage min, max, unitaire min, max]. Sous-traitance : préparation / expédition. */
    private const TIMES = [
        'CAO' => [1, 3, 0, 0],
        'LAS' => [0.2, 0.4, 0.01, 0.05],
        'BEND' => [0.3, 0.6, 0.02, 0.06],
        'ROUL' => [0.5, 1, 0.05, 0.1],
        'SCIE' => [0.1, 0.3, 0.01, 0.03],
        'WELD' => [0.3, 0.8, 0.08, 0.3],
        'EBAV' => [0, 0.1, 0.01, 0.03],
        'TOUR' => [0.5, 1, 0.05, 0.15],
        'FRAI' => [0.75, 1.5, 0.08, 0.25],
        'CTRL' => [0.1, 0.3, 0.005, 0.02],
        'subcontracting' => [1, 2, 0, 0],
        'default' => [0.2, 0.5, 0.01, 0.05],
    ];

    /** Horizon du planning à charger, en jours ouvrés, et taux de charge visé. */
    private const HORIZON_DAYS = 15;
    private const TARGET_LOAD = 0.9;
    /** Capacité affichée par l'écran de charge pour un poste sans ressource (h / jour). */
    private const DEFAULT_DAILY_CAPACITY = 8;

    /** Opérations en cours en même temps sur un poste (le reste attend). */
    private const IN_PROGRESS_PER_SERVICE = 2;

    private array $statusIds = [];
    private array $inProgress = [];
    private Collection $services;
    private Collection $components;
    private Collection $userIds;
    private int $defaultUnitId;
    private Carbon $now;

    /**
     * Une gamme par ligne de commande et par ligne de devis ouvert / envoyé,
     * tirée parmi des gammes types (découpe → pliage → peinture…), avec sa
     * matière et parfois des fournitures. Les opérations portent qty = 1 (leur
     * durée = réglage + temps unitaire × quantité de la ligne) ; un composant
     * porte la quantité par pièce.
     *
     * Planning : les temps des lignes à faire dans les 3 semaines sont calibrés
     * par poste pour viser ~90 % de la capacité affichée, puis les dates sont
     * posées par le calculateur de l'application — au plus tard depuis la date
     * de livraison, ou au plus tôt depuis maintenant si la ligne serait sinon
     * en retard. Au plus 2 opérations en cours par poste, démarrées maintenant ;
     * les opérations terminées sont datées jusqu'à la veille au soir et
     * marquées « Ne pas recalculer ».
     */
    public function run()
    {
        $this->now = Carbon::now();

        foreach (self::STATUSES as $order => $title) {
            $this->statusIds[$title] = Status::firstOrCreate(['title' => $title], ['order' => $order])->id;
        }

        $this->services = MethodsServices::query()->with('Ressources')->get()->keyBy('code');
        $this->components = Products::query()->get(['id', 'label', 'methods_units_id', 'purchased_price']);
        $this->userIds = User::query()->pluck('id');
        $this->defaultUnitId = MethodsUnits::query()->value('id') ?? MethodsUnits::factory()->create()->id;

        if ($this->services->where('type', 1)->isEmpty()) {
            $this->command?->warn('Aucun poste de type 1 : lancer MethodsServicesTableSeeder avant.');
            return;
        }

        // Écrites sans événement (ni journal d'activité ni réservation à chaque
        // tâche) ; les réservations sont recalculées une fois par composant à la fin.
        Task::withoutEvents(function () {
            $this->seedOrderLines();
            $this->seedQuoteLines();
        });

        $reservations = app(StockReservationService::class);
        Task::query()->whereNotNull('component_id')->whereNotNull('order_lines_id')->distinct()->pluck('component_id')
            ->each(fn ($productId) => $reservations->recomputeForProduct((int) $productId));
    }

    // ---------------------------------------------------------------------
    // Lignes de commande
    // ---------------------------------------------------------------------

    private function seedOrderLines(): void
    {
        $lines = OrderLines::query()
            ->with('order:id,statu')
            ->get(['id', 'orders_id', 'qty', 'delivered_remaining_qty', 'internal_delay']);

        // 1. Gammes en mémoire, avec l'avancement de chaque ligne.
        $plans = $lines->map(fn (OrderLines $line) => $this->planOrderLine($line));

        // 2. Calibrage des temps par poste sur ce qui tombe dans l'horizon.
        $factors = $this->calibrationFactors($plans);

        // 3. Écriture des tâches, puis des dates.
        $calculator = app(TaskDateCalculator::class);
        $delayResolver = app(InterOperationDelayResolver::class);
        $delayFn = fn (?int $from, ?int $to) => $delayResolver->hoursBetween($from, $to);

        foreach ($plans as $plan) {
            $tasks = $this->writeRouting('order_lines_id', $plan['line']->id, $plan['tasks'], $factors);
            $this->schedule($plan, $tasks, $calculator, $delayFn);

            $plan['line']->forceFill(['tasks_status' => $this->tasksStatus($plan)])->saveQuietly();
        }
    }

    /**
     * @return array{line: OrderLines, mode: string, tasks: array<int, array>}
     *   mode : done (ligne livrée), running (opérations commencées), open, canceled
     */
    private function planOrderLine(OrderLines $line): array
    {
        $orderStatu = (int) $line->order?->statu;
        $delivered = (float) $line->delivered_remaining_qty <= 0;

        $mode = match (true) {
            $orderStatu === 6 => 'canceled',
            $delivered => 'done',
            in_array($orderStatu, [2, 4], true) && random_int(1, 100) <= 75 => 'running',
            default => 'open',
        };

        $tasks = $this->buildRouting();
        $operations = array_keys(array_filter($tasks, fn ($t) => in_array($t['type'], [1, 7], true)));

        // Avancement : opérations terminées, puis la suivante, en cours seulement
        // si son poste a encore une place (sinon la ligne attend devant le poste).
        $finished = match ($mode) {
            'done' => count($operations),
            'running' => random_int(0, max(0, count($operations) - 1)),
            default => 0,
        };
        foreach ($operations as $rank => $key) {
            $serviceId = $tasks[$key]['service']->id;
            $startsNow = $mode === 'running' && $rank === $finished
                && ($this->inProgress[$serviceId] ?? 0) < self::IN_PROGRESS_PER_SERVICE;
            if ($startsNow) {
                $this->inProgress[$serviceId] = ($this->inProgress[$serviceId] ?? 0) + 1;
            }
            $tasks[$key]['status'] = match (true) {
                $mode === 'canceled' => 'Suspended',
                $rank < $finished => 'Finished',
                $startsNow => 'In progress',
                default => 'Open',
            };
        }
        if ($mode === 'running' && $finished === 0 && !in_array('In progress', array_column($tasks, 'status'), true)) {
            $mode = 'open';
        }
        foreach ($tasks as $key => $task) {
            if (!in_array($task['type'], [1, 7], true)) {
                // Matière sortie dès qu'une opération a commencé.
                $tasks[$key]['status'] = match (true) {
                    $mode === 'canceled' => 'Suspended',
                    $mode === 'done' || $mode === 'running' => 'Finished',
                    default => 'Open',
                };
            }
        }

        return ['line' => $line, 'mode' => $mode, 'tasks' => $tasks];
    }

    /**
     * Facteur par poste (méthode de l'écran de charge : capacité des
     * ressources ÷ 5, sinon 8 h / jour) pour que les opérations à faire dans
     * l'horizon chargent ~90 % de la capacité.
     */
    private function calibrationFactors(Collection $plans): array
    {
        $horizonEnd = $this->now->copy()->addWeekdays(self::HORIZON_DAYS);
        $raw = [];

        foreach ($plans as $plan) {
            // Ce que schedule() placera dans l'horizon : les lignes à livrer
            // d'ici là, et toute opération en cours (démarrée maintenant).
            $dueInHorizon = in_array($plan['mode'], ['open', 'running'], true)
                && Carbon::parse($plan['line']->internal_delay)->lte($horizonEnd);
            foreach ($plan['tasks'] as $task) {
                $counted = $task['status'] === 'In progress'
                    || ($dueInHorizon && $task['status'] === 'Open');
                if (in_array($task['type'], [1, 7], true) && $counted) {
                    $hours = $task['seting_time'] + $task['unit_time'] * (float) $plan['line']->qty;
                    $raw[$task['service']->id] = ($raw[$task['service']->id] ?? 0) + $hours;
                }
            }
        }

        $factors = [];
        foreach ($raw as $serviceId => $hours) {
            $service = $this->services->firstWhere('id', $serviceId);
            $target = $this->dailyCapacity($service) * self::HORIZON_DAYS * self::TARGET_LOAD;
            $factors[$serviceId] = $hours > 0 ? max(0.02, min(5, $target / $hours)) : 1;
        }

        return $factors;
    }

    private function dailyCapacity(MethodsServices $service): float
    {
        $capacity = $service->Ressources->sum(
            fn (MethodsRessources $resource) => $resource->capacity / max(1, $resource->services()->count())
        ) / MethodsRessources::WORKING_DAYS_PER_WEEK;

        return $capacity > 0 ? $capacity : self::DEFAULT_DAILY_CAPACITY;
    }

    /**
     * Dates posées par le calculateur de l'application (même enchaînement par
     * `ordre` que « Calculer les dates »).
     */
    private function schedule(array $plan, Collection $tasks, TaskDateCalculator $calculator, callable $delayFn): void
    {
        foreach ($tasks as $task) {
            $task->setRelation('OrderLines', $plan['line']);
        }
        $operations = $tasks->filter(fn (Task $t) => in_array((int) $t->type, [1, 7], true))->sortBy('ordre')->values();
        if ($operations->isEmpty() || $plan['mode'] === 'canceled') {
            return;
        }

        $finishedId = $this->statusIds['Finished'];
        $finished = $operations->filter(fn (Task $t) => $t->status_id === $finishedId);
        $todo = $operations->reject(fn (Task $t) => $t->status_id === $finishedId);

        $entries = [];
        if ($plan['mode'] === 'done') {
            // Livrée : toute la gamme avant la date de livraison (au plus tard
            // la veille au soir : une ligne livrée d'une commande en partie
            // livrée garde la date, future, de la commande).
            $anchor = Carbon::parse($plan['line']->internal_delay)->setTime(17, 0);
            $entries = $calculator->chainTasks($finished->reverse(), $anchor->min($this->lastWorkingEvening()), $delayFn);
        } else {
            // Opérations terminées : jusqu'à la veille au soir.
            if ($finished->isNotEmpty()) {
                $entries = $calculator->chainTasks($finished->reverse(), $this->lastWorkingEvening(), $delayFn);
            }
            // Opération en cours : démarrée maintenant.
            $earliest = $this->now->copy();
            $current = $todo->first(fn (Task $t) => $t->status_id === $this->statusIds['In progress']);
            if ($current) {
                $placed = $calculator->chainTasksForward([$current], $earliest, $delayFn);
                $entries = array_merge($entries, $placed);
                $earliest = $placed[0]['end']->copy();
                $todo = $todo->reject(fn (Task $t) => $t->is($current))->values();
            }
            // Reste de la gamme : au plus tard depuis la livraison, sinon (ligne
            // qui serait en retard) au plus tôt après ce qui précède.
            if ($todo->isNotEmpty()) {
                $alap = $calculator->chainTasks($todo->reverse(), Carbon::parse($plan['line']->internal_delay), $delayFn);
                $entries = array_merge($entries, collect($alap)->min('start')->lt($earliest)
                    ? $calculator->chainTasksForward($todo, $earliest, $delayFn)
                    : $alap);
            }
        }

        foreach ($entries as $entry) {
            $entry['task']->forceFill([
                'start_date' => $entry['start'],
                'end_date' => $entry['end'],
                'due_date' => $entry['end'],
                // Une opération terminée ne bouge plus au prochain « Calculer les dates ».
                'not_recalculate' => $entry['task']->status_id === $finishedId,
            ])->saveQuietly();
        }
    }

    private function lastWorkingEvening(): Carbon
    {
        $day = $this->now->copy()->subDay();
        while ($day->isWeekend()) {
            $day->subDay();
        }

        return $day->setTime(17, 0);
    }

    private function tasksStatus(array $plan): int
    {
        $statuses = array_column($plan['tasks'], 'status');

        return match (true) {
            $statuses === [] => 1,
            $plan['mode'] === 'done' => 4,
            in_array('Finished', $statuses, true) || in_array('In progress', $statuses, true) => 3,
            default => 2,
        };
    }

    // ---------------------------------------------------------------------
    // Lignes de devis
    // ---------------------------------------------------------------------

    private function seedQuoteLines(): void
    {
        $openQuoteIds = Quotes::query()->whereIn('statu', [1, 2])->pluck('id');

        QuoteLines::query()
            ->whereIn('quotes_id', $openQuoteIds)
            ->pluck('id')
            ->each(function (int $lineId) {
                $tasks = array_map(fn ($t) => $t + ['status' => 'Open'], $this->buildRouting());
                $this->writeRouting('quote_lines_id', $lineId, $tasks, []);
            });
    }

    // ---------------------------------------------------------------------
    // Gamme
    // ---------------------------------------------------------------------

    /**
     * Gamme d'une ligne, en mémoire : opérations dans l'ordre de la gamme type,
     * puis matière et fournitures. Temps en heures, avant calibrage.
     *
     * @return array<int, array{service: MethodsServices, type: int, seting_time: float, unit_time: float, qty: float, component: ?Products}>
     */
    private function buildRouting(): array
    {
        $routing = $this->pickRouting();
        $tasks = [];

        foreach ($routing['ops'] as $code) {
            $service = $this->services->get($code);
            $isSubcontracting = (int) $service->type === 7;
            [$setupMin, $setupMax, $unitMin, $unitMax] = self::TIMES[$isSubcontracting ? 'subcontracting' : $code] ?? self::TIMES['default'];

            $tasks[] = [
                'service' => $service,
                'type' => (int) $service->type,
                'seting_time' => fake()->randomFloat(2, $setupMin, $setupMax),
                'unit_time' => fake()->randomFloat(3, $unitMin, $unitMax),
                'qty' => 1,
                'component' => null,
            ];
        }

        $materials = array_filter([
            $routing['material'] ?? null,
            !empty($routing['supplies']) && random_int(1, 100) <= 60 ? 'PUR' : null,
        ]);
        foreach ($materials as $code) {
            $service = $this->services->get($code);
            if (!$service || $this->components->isEmpty()) {
                continue;
            }
            $tasks[] = [
                'service' => $service,
                'type' => (int) $service->type,
                'seting_time' => 0,
                'unit_time' => 0,
                'qty' => random_int(1, 4),
                'component' => $this->components->random(),
            ];
        }

        return $tasks;
    }

    /** Gamme type tirée au poids, réduite aux postes présents en base. */
    private function pickRouting(): array
    {
        $available = collect(self::ROUTINGS)
            ->map(fn ($r) => ['ops' => array_values(array_filter($r['ops'], fn ($code) => $this->services->has($code)))] + $r)
            ->filter(fn ($r) => $r['ops'] !== []);

        if ($available->isEmpty()) {
            // Trames de devis non chargées : enchaînement des postes de type 1 par ordre.
            return ['ops' => $this->services->where('type', 1)->sortBy('ordre')->keys()->take(3)->all(), 'material' => 'MAT'];
        }

        $draw = random_int(1, $available->sum('weight'));
        foreach ($available as $routing) {
            if (($draw -= $routing['weight']) <= 0) {
                return $routing;
            }
        }

        return $available->first();
    }

    /**
     * @param  array<int, float>  $factors  facteur de temps par poste
     * @return Collection<int, Task>
     */
    private function writeRouting(string $foreignKey, int $lineId, array $tasks, array $factors): Collection
    {
        $created = collect();
        $ordre = 0;

        foreach ($tasks as $task) {
            $service = $task['service'];
            $factor = $factors[$service->id] ?? 1;
            $setup = round($task['seting_time'] * $factor, 2);
            $unitTime = $task['unit_time'] > 0 ? max(0.001, round($task['unit_time'] * $factor, 3)) : 0;
            $component = $task['component'];

            if ($component) {
                $unitCost = round((float) ($component->purchased_price ?? fake()->randomFloat(2, 1, 50)), 2);
            } elseif ($task['type'] === 7) {
                $unitCost = fake()->randomFloat(2, 1, 10);
            } else {
                $unitCost = round((float) $service->hourly_rate * $unitTime, 2);
            }

            $created->push(Task::create([
                $foreignKey => $lineId,
                'label' => $component->label ?? $service->label,
                'ordre' => $ordre += 10,
                'methods_services_id' => $service->id,
                'component_id' => $component?->id,
                'type' => $task['type'],
                'qty' => $task['qty'],
                'qty_init' => $task['qty'],
                'seting_time' => $setup,
                'unit_time' => $unitTime,
                'unit_cost' => $unitCost,
                'unit_price' => round($unitCost * (1 + (float) $service->margin / 100), 2),
                'methods_units_id' => $component->methods_units_id ?? $this->defaultUnitId,
                'status_id' => $this->statusIds[$task['status']],
                'priority' => fake()->randomElement([Task::PRIORITY_HIGH, Task::PRIORITY_MEDIUM, Task::PRIORITY_LOW]),
                'user_id' => $this->userIds->isNotEmpty() ? $this->userIds->random() : null,
            ]));
        }

        return $created;
    }
}
