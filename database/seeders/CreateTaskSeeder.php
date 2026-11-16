<?php

namespace Database\Seeders;

use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\StockReservationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CreateTaskSeeder extends Seeder
{
    /** Statuts de tâche attendus par l'application (titres cherchés tels quels). */
    private const STATUSES = ['Open', 'Started', 'In progress', 'Finished', 'Suspended', 'To RFQ', 'RFQ in progress', 'Outsourced', 'Supplied'];

    private array $statusIds = [];
    private Collection $operations;
    private Collection $subcontracting;
    private Collection $materials;
    private Collection $components;
    private Collection $userIds;
    private int $defaultUnitId;

    /**
     * Une gamme par ligne de commande et par ligne de devis ouvert / envoyé :
     * 1 à 3 opérations internes (type 1), parfois une sous-traitance (type 7),
     * puis 0 à 2 composants (types 2-6, 8) rattachés à un article. Les
     * opérations portent qty = 1 (par pièce, comme l'écran de gamme) ; un
     * composant porte la quantité par pièce (besoin = qty × quantité de ligne).
     * L'avancement suit celui de la commande.
     */
    public function run()
    {
        foreach (self::STATUSES as $order => $title) {
            $this->statusIds[$title] = Status::firstOrCreate(['title' => $title], ['order' => $order])->id;
        }

        $services = MethodsServices::query()->orderBy('ordre')->get();
        $this->operations = $services->where('type', 1)->values();
        $this->subcontracting = $services->where('type', 7)->values();
        $this->materials = $services->whereIn('type', [2, 3, 4, 5, 6, 8])->values();
        $this->components = Products::query()->get(['id', 'label', 'methods_units_id', 'purchased_price']);
        $this->userIds = User::query()->pluck('id');
        $this->defaultUnitId = MethodsUnits::query()->value('id') ?? MethodsUnits::factory()->create()->id;

        if ($this->operations->isEmpty()) {
            $this->command?->warn('Aucun poste de type 1 : lancer MethodsServicesTableSeeder avant.');
            return;
        }

        // Écrites sans événement (ni journal d'activité ni réservation à chaque
        // tâche) ; les réservations sont recalculées une fois par composant à la fin.
        Task::withoutEvents(function () {
            OrderLines::query()
                ->with('order:id,statu')
                ->get(['id', 'orders_id', 'qty', 'delivered_remaining_qty', 'delivery_date'])
                ->each(fn (OrderLines $line) => $this->seedOrderLine($line));

            $openQuoteIds = Quotes::query()->whereIn('statu', [1, 2])->pluck('id');
            QuoteLines::query()
                ->whereIn('quotes_id', $openQuoteIds)
                ->get(['id'])
                ->each(fn (QuoteLines $line) => $this->createRouting('quote_lines_id', $line->id, 0, null));
        });

        $reservations = app(StockReservationService::class);
        Task::query()->whereNotNull('component_id')->distinct()->pluck('component_id')
            ->each(fn ($productId) => $reservations->recomputeForProduct((int) $productId));
    }

    private function seedOrderLine(OrderLines $line): void
    {
        $orderStatu = (int) $line->order?->statu;
        $lineDelivered = (float) $line->delivered_remaining_qty <= 0;

        // Opérations terminées : tout si la ligne est livrée, une partie si la
        // commande est en cours ou livrée en partie, aucune sinon.
        $progress = match (true) {
            $lineDelivered => PHP_INT_MAX,
            in_array($orderStatu, [2, 4], true) => random_int(0, 3),
            default => 0,
        };

        $created = $this->createRouting('order_lines_id', $line->id, $progress, $line->delivery_date);

        $tasksStatus = match (true) {
            $created === 0 => 1,
            $lineDelivered => 4,
            $progress > 0 => 3,
            default => 2,
        };
        $line->forceFill(['tasks_status' => $tasksStatus])->saveQuietly();
    }

    /**
     * @param  int  $finishedOps  nombre d'opérations déjà terminées (la suivante est en cours)
     * @return int  nombre de tâches créées
     */
    private function createRouting(string $foreignKey, int $lineId, int $finishedOps, $deliveryDate): int
    {
        $ops = $this->operations->random(min(random_int(1, 3), $this->operations->count()))
            ->sortBy('ordre')->values();
        if ($this->subcontracting->isNotEmpty() && random_int(1, 100) <= 30) {
            $ops->push($this->subcontracting->random());
        }

        $deadline = $deliveryDate ? Carbon::parse($deliveryDate) : null;
        $ordre = 0;
        $created = 0;

        foreach ($ops as $index => $service) {
            $status = match (true) {
                $index < $finishedOps => 'Finished',
                $index === $finishedOps && $finishedOps > 0 => 'In progress',
                default => 'Open',
            };
            $unitTime = $service->type == 7 ? 0 : fake()->randomFloat(3, 0.01, 0.5);
            $unitCost = $service->type == 7
                ? fake()->randomFloat(2, 1, 10)
                : round((float) $service->hourly_rate * $unitTime, 2);

            $this->createTask($foreignKey, $lineId, $ordre += 10, $service, [
                'qty' => 1,
                'seting_time' => $service->type == 7 ? 0 : fake()->randomFloat(2, 0.1, 1.5),
                'unit_time' => $unitTime,
                'unit_cost' => $unitCost,
                'unit_price' => round($unitCost * (1 + (float) $service->margin / 100), 2),
                'status_id' => $this->statusIds[$status],
                // Échéances échelonnées avant la date de livraison de la ligne.
                'due_date' => $deadline?->copy()->subDays(($ops->count() - $index) * 2),
            ]);
            $created++;
        }

        if ($this->materials->isNotEmpty() && $this->components->isNotEmpty()) {
            for ($i = random_int(0, 2); $i > 0; $i--) {
                $component = $this->components->random();
                $unitCost = (float) ($component->purchased_price ?? fake()->randomFloat(2, 1, 50));
                $service = $this->materials->random();

                $this->createTask($foreignKey, $lineId, $ordre += 10, $service, [
                    'label' => $component->label,
                    'component_id' => $component->id,
                    'qty' => random_int(1, 4),
                    'methods_units_id' => $component->methods_units_id ?? $this->defaultUnitId,
                    'unit_cost' => round($unitCost, 2),
                    'unit_price' => round($unitCost * (1 + (float) $service->margin / 100), 2),
                    'status_id' => $this->statusIds[$finishedOps > 0 ? 'Finished' : 'Open'],
                    'due_date' => $deadline?->copy()->subDays(($ops->count() + 1) * 2),
                ]);
                $created++;
            }
        }

        return $created;
    }

    private function createTask(string $foreignKey, int $lineId, int $ordre, MethodsServices $service, array $attributes): void
    {
        Task::create($attributes + [
            $foreignKey => $lineId,
            'label' => $service->label,
            'ordre' => $ordre,
            'methods_services_id' => $service->id,
            'type' => $service->type,
            'qty_init' => $attributes['qty'],
            'seting_time' => 0,
            'unit_time' => 0,
            'methods_units_id' => $this->defaultUnitId,
            'priority' => fake()->randomElement([Task::PRIORITY_HIGH, Task::PRIORITY_MEDIUM, Task::PRIORITY_LOW]),
            'user_id' => $this->userIds->isNotEmpty() ? $this->userIds->random() : null,
        ]);
    }
}
