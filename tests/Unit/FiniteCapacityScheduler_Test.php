<?php

namespace Tests\Unit;

use App\Models\Methods\MethodsRessources;
use App\Models\Planning\Task;
use App\Models\Workflow\OrderLines;
use App\Services\Planning\FiniteCapacityScheduler;
use App\Services\ResourceCapacityService;
use App\Services\TaskDateCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests du scheduler capacité finie sans DB — on isole le solveur en
 * fournissant :
 *   - un stub `ResourceCapacityService` qui renvoie une capacité fixe par jour
 *   - des `Task` en mémoire avec une relation `resources` déjà chargée
 *   - des `OrderLines` en mémoire avec un anchor `internal_delay` fixe
 *
 * Cette approche évite de dépendre du schéma SQL (task_resources, régime
 * horaire, etc.) — elle vérifie strictement la logique de placement +
 * décalage.
 */
class FiniteCapacityScheduler_Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // SQLite in-memory : le solveur appelle WorkingTime::isWorkingInstant
        // qui interroge times_banck_holidays et work_shift_patterns. Sans les
        // tables, on retombe sur le fallback lun-ven 8h-18h — exactement ce
        // qu'on veut pour tester le placement pur.
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
    }

    private function makeScheduler(array $availableHoursByDate = []): FiniteCapacityScheduler
    {
        // Stub : capacité fixe par date (par défaut 4h/j — on veut pouvoir
        // déborder facilement).
        $capacity = new class($availableHoursByDate) extends ResourceCapacityService {
            public function __construct(private array $byDate) {}
            public function availableHours(MethodsRessources $resource, Carbon $date): float
            {
                return (float) ($this->byDate[$date->toDateString()] ?? 4.0);
            }
        };

        return new FiniteCapacityScheduler(new TaskDateCalculator(), $capacity);
    }

    private function makeResource(int $id, string $label = 'M1'): MethodsRessources
    {
        $resource = new MethodsRessources();
        $resource->id       = $id;
        $resource->label    = $label;
        $resource->is_labor = false;
        $resource->exists   = true;
        return $resource;
    }

    private function makeTask(
        int $id,
        int $ordre,
        float $duration,
        ?MethodsRessources $resource = null,
        float $loadFactor = 1.0,
    ): Task {
        $task = new Task();
        $task->id           = $id;
        $task->label        = 'T' . $id;
        $task->ordre        = $ordre;
        $task->seting_time  = 0;
        $task->unit_time    = $duration;
        $task->qty          = 1;
        $task->methods_services_id = null;
        $task->exists       = true;

        // Force la relation `resources` chargée : la ressource unique porte
        // le pivot load_factor.
        $resources = new EloquentCollection();
        if ($resource !== null) {
            $resource->pivot = (object) ['load_factor' => $loadFactor];
            $resources->push($resource);
        }
        $task->setRelation('resources', $resources);

        // Court-circuite les accès OrderLines / QuoteLines de TotalTime().
        $task->cachedOrderQtyLine = 1.0;

        return $task;
    }

    private function makeOrderLine(int $id, int $orderId, Carbon $anchor, array $tasks): OrderLines
    {
        $line = new OrderLines();
        $line->id             = $id;
        $line->orders_id      = $orderId;
        $line->internal_delay = $anchor->toDateTimeString();
        $line->start_date     = $anchor->copy()->subDays(2)->toDateTimeString();
        $line->exists         = true;

        $collection = Collection::make($tasks);
        $line->setRelation('Task', $collection);

        return $line;
    }

    public function test_infinite_mode_matches_the_backscheduling_legacy(): void
    {
        // ALAP infinite : la tâche de 2h finit exactement à l'ancre, sans
        // regarder la capacité — équivaut à TaskDateCalculator::chainTasks.
        $scheduler = $this->makeScheduler();
        $resource  = $this->makeResource(1);
        $task      = $this->makeTask(10, 10, 2.0, $resource);
        $anchor    = Carbon::create(2024, 5, 6, 12, 0, 0);
        $line      = $this->makeOrderLine(1, 100, $anchor, [$task]);

        $result = $scheduler->chainOrderLine($line, 'alap', FiniteCapacityScheduler::MODE_INFINITE);

        $this->assertCount(1, $result);
        $this->assertSame('2024-05-06 12:00:00', $result[0]['end']->format('Y-m-d H:i:s'));
        $this->assertSame('2024-05-06 10:00:00', $result[0]['start']->format('Y-m-d H:i:s'));
    }

    public function test_finite_mode_shifts_second_of_earlier_when_day_is_full(): void
    {
        // Deux OF de 4h chacun sur la même machine, capacité 4h/j :
        // OF1 (traité en premier, plus urgent) → occupe lundi 14h-18h
        // OF2 → conflit lundi (déjà plein) → décalé au vendredi précédent
        $scheduler = $this->makeScheduler([
            '2024-05-03' => 4.0, // vendredi
            '2024-05-06' => 4.0, // lundi
        ]);
        $resource = $this->makeResource(1);

        $task1 = $this->makeTask(10, 10, 4.0, $resource);
        $task2 = $this->makeTask(20, 10, 4.0, $resource);

        $anchor = Carbon::create(2024, 5, 6, 18, 0, 0); // lundi 18h
        $line1  = $this->makeOrderLine(1, 100, $anchor, [$task1]);
        $line2  = $this->makeOrderLine(2, 200, $anchor, [$task2]);

        $r1 = $scheduler->chainOrderLine($line1, 'alap', FiniteCapacityScheduler::MODE_FINITE);
        $r2 = $scheduler->chainOrderLine($line2, 'alap', FiniteCapacityScheduler::MODE_FINITE);

        // OF1 : rempli le lundi entier.
        $this->assertSame('2024-05-06 18:00:00', $r1[0]['end']->format('Y-m-d H:i:s'));
        // OF2 : lundi plein → décalé au vendredi précédent (ou avant), pas le lundi.
        $this->assertNotSame(
            '2024-05-06',
            $r2[0]['end']->toDateString(),
            'OF2 devrait avoir été décalé hors du lundi saturé',
        );
        $this->assertTrue(
            $r2[0]['end']->lt(Carbon::create(2024, 5, 6, 0, 0, 0)),
            'OF2 devrait finir avant le lundi'
        );
    }

    public function test_finite_asap_shifts_later_when_day_is_full(): void
    {
        // Symétrique ASAP : deux OF de 4h, capacité 4h/j, ancres identiques.
        // OF1 → occupe lundi. OF2 → décalé au mardi (jour suivant ouvré).
        $scheduler = $this->makeScheduler([
            '2024-05-06' => 4.0, // lundi
            '2024-05-07' => 4.0, // mardi
        ]);
        $resource = $this->makeResource(1);

        $task1 = $this->makeTask(10, 10, 4.0, $resource);
        $task2 = $this->makeTask(20, 10, 4.0, $resource);

        $anchor = Carbon::create(2024, 5, 6, 8, 0, 0); // lundi 8h
        $line1  = $this->makeOrderLine(1, 100, $anchor, [$task1]);
        $line2  = $this->makeOrderLine(2, 200, $anchor, [$task2]);

        // Les deux ancres = lundi, mais start_date = anchor pour l'ASAP dans
        // notre stub d'OrderLine. On force via l'anchor du makeOrderLine.
        $line1->start_date = $anchor->toDateTimeString();
        $line2->start_date = $anchor->toDateTimeString();

        $r1 = $scheduler->chainOrderLine($line1, 'asap', FiniteCapacityScheduler::MODE_FINITE);
        $r2 = $scheduler->chainOrderLine($line2, 'asap', FiniteCapacityScheduler::MODE_FINITE);

        // OF1 : rempli le lundi entier.
        $this->assertSame('2024-05-06', $r1[0]['start']->toDateString());
        // OF2 : lundi plein → décalé au mardi (jour suivant).
        $this->assertTrue(
            $r2[0]['start']->gte(Carbon::create(2024, 5, 7, 0, 0, 0)),
            'OF2 devrait démarrer au plus tôt le mardi'
        );
    }

    public function test_finite_reports_unfitted_when_no_slot_is_ever_available(): void
    {
        // Ressource sans aucune capacité : le scheduler abandonne au bout de
        // MAX_SHIFT_DAYS et flague la tâche.
        $scheduler = $this->makeScheduler(); // par défaut 4h/j
        $resource  = $this->makeResource(1);

        // Charge >>> capacité (100h sur une ressource 4h/j) — dépasse même
        // sur la fenêtre de 60 jours pour la tâche seule. Mais notre solver
        // shift-to-fit teste heure-par-heure : une tâche de 100h ne peut PAS
        // tenir en 60 jours × 4h = 240h... elle tiendrait. Il faut plus.
        $task   = $this->makeTask(10, 10, 500.0, $resource);
        $anchor = Carbon::create(2024, 5, 6, 12, 0, 0);
        $line   = $this->makeOrderLine(1, 100, $anchor, [$task]);

        $result = $scheduler->chainOrderLine($line, 'alap', FiniteCapacityScheduler::MODE_FINITE);

        // La tâche est posée quand même (au dernier décalage tenté).
        $this->assertCount(1, $result);
        // Et signalée dans les messages.
        $messages = $scheduler->unfittedMessages();
        $this->assertNotEmpty($messages, 'La tâche débordante devrait être signalée');
        $this->assertStringContainsString('#100', $messages[0]);
    }

    public function test_reset_wipes_the_running_load(): void
    {
        $scheduler = $this->makeScheduler(['2024-05-06' => 4.0]);
        $resource  = $this->makeResource(1);

        // Premier run : OF1 remplit le lundi.
        $t1 = $this->makeTask(10, 10, 4.0, $resource);
        $l1 = $this->makeOrderLine(1, 100, Carbon::create(2024, 5, 6, 18, 0, 0), [$t1]);
        $r1 = $scheduler->chainOrderLine($l1, 'alap', FiniteCapacityScheduler::MODE_FINITE);
        $this->assertSame('2024-05-06', $r1[0]['end']->toDateString());

        // Reset puis un nouvel OF identique doit pouvoir retenir le même jour.
        $scheduler->reset();
        $t2 = $this->makeTask(20, 10, 4.0, $resource);
        $l2 = $this->makeOrderLine(2, 200, Carbon::create(2024, 5, 6, 18, 0, 0), [$t2]);
        $r2 = $scheduler->chainOrderLine($l2, 'alap', FiniteCapacityScheduler::MODE_FINITE);
        $this->assertSame('2024-05-06', $r2[0]['end']->toDateString());
    }
}
