<?php

namespace Tests\Unit;

use Tests\TestCase;
use Carbon\Carbon;
use App\Services\TaskDateCalculator;
use App\Services\Planning\InterOperationDelayResolver;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsRessources;
use App\Models\Planning\Task;
use App\Models\Planning\OperationTransitionDelay;
use App\Models\Times\TimesBanckHoliday;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskDateCalculatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        if (!Schema::hasTable('methods_services')) {
            Schema::create('methods_services', function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->integer('ordre')->default(1);
                $table->string('label');
                $table->integer('type')->default(1);
                $table->double('hourly_rate')->default(0);
                $table->double('margin')->default(0);
                $table->string('color')->nullable();
                $table->string('picture')->nullable();
                $table->integer('companies_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('methods_units')) {
            Schema::create('methods_units', function (Blueprint $table) {
                $table->id();
                $table->string('code');
                $table->string('label');
                $table->string('type')->nullable();
                $table->boolean('default')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->string('label');
                $table->integer('ordre')->default(1);
                $table->foreignId('methods_services_id')->nullable();
                $table->foreignId('methods_units_id')->nullable();
                $table->float('seting_time')->default(0);
                $table->float('unit_time')->default(0);
                $table->integer('qty')->default(1);
                $table->integer('qty_init')->default(1);
                $table->integer('type')->default(1);
                $table->integer('status_id')->default(1);
                $table->timestamp('start_date')->nullable();
                $table->timestamp('end_date')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('methods_ressources')) {
            Schema::create('methods_ressources', function (Blueprint $table) {
                $table->id();
                $table->integer('ordre')->default(1);
                $table->string('code')->nullable();
                $table->string('label');
                $table->integer('capacity')->default(1);
                $table->foreignId('methods_services_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('times_banck_holidays')) {
            Schema::create('times_banck_holidays', function (Blueprint $table) {
                $table->id();
                $table->boolean('fixed')->default(true);
                $table->date('date');
                $table->string('label');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('operation_transition_delays')) {
            Schema::create('operation_transition_delays', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_service_id');
                $table->foreignId('to_service_id');
                $table->decimal('transfer_hours', 8, 2)->default(0);
                $table->timestamps();
                $table->unique(['from_service_id', 'to_service_id']);
            });
        } else {
            OperationTransitionDelay::query()->delete();
        }

        // Neutralise les défauts config pour que chaque test décide de son propre délai.
        config()->set('planning.inter_operation_hours', 0);
        config()->set('planning.min_operation_gap_hours', 0);
    }

    public function test_adjustment_of_weekends_and_holidays(): void
    {
        TimesBanckHoliday::create(['fixed' => false, 'date' => '2024-05-08', 'label' => 'Holiday']);
        $calculator = new TaskDateCalculator();

        $this->assertSame('2024-05-03', $calculator->adjustForWeekendsAndHolidays(Carbon::create(2024, 5, 4))->toDateString());
        $this->assertSame('2024-05-07', $calculator->adjustForWeekendsAndHolidays(Carbon::create(2024, 5, 8))->toDateString());
    }

    public function test_adjustment_of_working_hours(): void
    {
        $calculator = new TaskDateCalculator();
        $date = Carbon::create(2024, 5, 6, 10, 0, 0); // Monday 10:00
        $adjusted = $calculator->adjustForWorkingHours($date, 6 * 3600);
        $this->assertEquals('2024-05-03 14:00:00', $adjusted->format('Y-m-d H:i:s'));
    }

    public function test_calculates_start_and_end_for_simple_task(): void
    {
        $service = MethodsServicesFactory::new()->create();
        $unit = MethodsUnitsFactory::new()->create();
        $task = TaskTestFactory::new()->create([
            'methods_services_id' => $service->id,
            'methods_units_id' => $unit->id,
            'seting_time' => 1,
            'unit_time' => 1,
            'qty' => 1,
            'qty_init' => 1,
        ]);

        $calculator = new TaskDateCalculator();
        $end = Carbon::create(2024, 5, 3, 18, 0, 0);
        [$start, $finish] = $calculator->calculateTaskDates($task, $end);

        $this->assertEquals('2024-05-03 16:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-05-03 18:00:00', $finish->format('Y-m-d H:i:s'));
    }

    public function test_chain_tasks_places_two_consecutive_tasks_without_compounding(): void
    {
        // Régression : l'ancien enchaînement cumulait la durée à chaque itération,
        // ce qui décalait la 2e tâche du double de sa durée. Deux tâches de 2h
        // doivent finir/commencer bord à bord si aucun délai n'est configuré.
        $service = MethodsServicesFactory::new()->create();
        $unit = MethodsUnitsFactory::new()->create();

        $downstream = TaskTestFactory::new()->create([
            'methods_services_id' => $service->id,
            'methods_units_id' => $unit->id,
            'ordre' => 20,
            'seting_time' => 0,
            'unit_time' => 1,
            'qty' => 2,
        ]);
        $upstream = TaskTestFactory::new()->create([
            'methods_services_id' => $service->id,
            'methods_units_id' => $unit->id,
            'ordre' => 10,
            'seting_time' => 0,
            'unit_time' => 1,
            'qty' => 2,
        ]);

        $calculator = new TaskDateCalculator();
        // Vendredi 3 mai 2024 18:00 — ancre alignée sur un jour ouvré.
        $anchor = Carbon::create(2024, 5, 3, 18, 0, 0);

        $chained = $calculator->chainTasks(collect([$downstream, $upstream]), $anchor);

        $this->assertCount(2, $chained);
        $this->assertSame('2024-05-03 16:00:00', $chained[0]['start']->format('Y-m-d H:i:s'));
        $this->assertSame('2024-05-03 18:00:00', $chained[0]['end']->format('Y-m-d H:i:s'));
        $this->assertSame('2024-05-03 14:00:00', $chained[1]['start']->format('Y-m-d H:i:s'));
        $this->assertSame('2024-05-03 16:00:00', $chained[1]['end']->format('Y-m-d H:i:s'));
    }

    public function test_chain_tasks_applies_default_inter_operation_delay(): void
    {
        config()->set('planning.inter_operation_hours', 1);
        $service = MethodsServicesFactory::new()->create();
        $unit = MethodsUnitsFactory::new()->create();

        $downstream = TaskTestFactory::new()->create([
            'methods_services_id' => $service->id, 'methods_units_id' => $unit->id,
            'ordre' => 20, 'seting_time' => 0, 'unit_time' => 1, 'qty' => 2,
        ]);
        $upstream = TaskTestFactory::new()->create([
            'methods_services_id' => $service->id, 'methods_units_id' => $unit->id,
            'ordre' => 10, 'seting_time' => 0, 'unit_time' => 1, 'qty' => 2,
        ]);

        $resolver = new InterOperationDelayResolver();
        $calculator = new TaskDateCalculator();
        $anchor = Carbon::create(2024, 5, 3, 18, 0, 0);

        $chained = $calculator->chainTasks(
            collect([$downstream, $upstream]),
            $anchor,
            fn (?int $from, ?int $to) => $resolver->hoursBetween($from, $to),
        );

        // Aval inchangé
        $this->assertSame('2024-05-03 16:00:00', $chained[0]['start']->format('Y-m-d H:i:s'));
        // Amont décalé d'1h vers l'amont (délai) — 2h de durée → start = 13h
        $this->assertSame('2024-05-03 13:00:00', $chained[1]['start']->format('Y-m-d H:i:s'));
        $this->assertSame('2024-05-03 15:00:00', $chained[1]['end']->format('Y-m-d H:i:s'));
    }

    public function test_chain_tasks_prefers_pair_override_over_default_delay(): void
    {
        // Pair-spécifique 3h prime sur défaut config 1h.
        config()->set('planning.inter_operation_hours', 1);

        $unit = MethodsUnitsFactory::new()->create();
        $laser = MethodsServicesFactory::new()->create(['code' => 'LASER', 'label' => 'Laser']);
        $soud  = MethodsServicesFactory::new()->create(['code' => 'SOUD',  'label' => 'Soudure']);

        OperationTransitionDelay::create([
            'from_service_id' => $laser->id,
            'to_service_id'   => $soud->id,
            'transfer_hours'  => 3,
        ]);

        $downstream = TaskTestFactory::new()->create([
            'methods_services_id' => $soud->id, 'methods_units_id' => $unit->id,
            'ordre' => 20, 'seting_time' => 0, 'unit_time' => 1, 'qty' => 2,
        ]);
        $upstream = TaskTestFactory::new()->create([
            'methods_services_id' => $laser->id, 'methods_units_id' => $unit->id,
            'ordre' => 10, 'seting_time' => 0, 'unit_time' => 1, 'qty' => 2,
        ]);

        $resolver = new InterOperationDelayResolver();
        $calculator = new TaskDateCalculator();
        $anchor = Carbon::create(2024, 5, 3, 18, 0, 0);

        $chained = $calculator->chainTasks(
            collect([$downstream, $upstream]),
            $anchor,
            fn (?int $from, ?int $to) => $resolver->hoursBetween($from, $to),
        );

        // Aval inchangé
        $this->assertSame('2024-05-03 16:00:00', $chained[0]['start']->format('Y-m-d H:i:s'));
        // Amont : cursor 16h - 3h (paire) = 13h → end=13h, 2h durée → start=11h
        $this->assertSame('2024-05-03 11:00:00', $chained[1]['start']->format('Y-m-d H:i:s'));
        $this->assertSame('2024-05-03 13:00:00', $chained[1]['end']->format('Y-m-d H:i:s'));
    }

    public function test_resolver_falls_back_to_default_when_pair_is_missing(): void
    {
        config()->set('planning.inter_operation_hours', 2);
        config()->set('planning.min_operation_gap_hours', 0.5);

        $resolver = new InterOperationDelayResolver();

        // Aucune paire en base → 2h défaut + 0.5h écart minimum = 2.5h
        $this->assertSame(2.5, $resolver->hoursBetween(1, 2));
        // transfer seul (sans écart minimum)
        $this->assertSame(2.0, $resolver->transferHoursBetween(1, 2));
        // Aucun service → 0 transfer, mais l'écart minimum reste appliqué
        $this->assertSame(0.5, $resolver->hoursBetween(null, 2));
        $this->assertSame(0.0, $resolver->transferHoursBetween(null, 2));
    }

    public function test_forward_adjustment_of_weekends_and_holidays(): void
    {
        // Miroir avant : samedi 4 mai → lundi 6 ; férié isolé → jour ouvré suivant.
        TimesBanckHoliday::create(['fixed' => false, 'date' => '2024-05-08', 'label' => 'Holiday']);
        $calculator = new TaskDateCalculator();

        $this->assertSame('2024-05-06', $calculator->adjustForWeekendsAndHolidaysForward(Carbon::create(2024, 5, 4))->toDateString());
        $this->assertSame('2024-05-09', $calculator->adjustForWeekendsAndHolidaysForward(Carbon::create(2024, 5, 8))->toDateString());
    }

    public function test_chain_tasks_forward_places_two_consecutive_tasks_from_anchor(): void
    {
        // Symétrique de test_chain_tasks_places_two_consecutive_tasks : deux
        // tâches de 2h démarrent bord à bord à l'ancre (pas de délai config).
        $service = MethodsServicesFactory::new()->create();
        $unit = MethodsUnitsFactory::new()->create();

        $upstream = TaskTestFactory::new()->create([
            'methods_services_id' => $service->id,
            'methods_units_id' => $unit->id,
            'ordre' => 10,
            'seting_time' => 0,
            'unit_time' => 1,
            'qty' => 2,
        ]);
        $downstream = TaskTestFactory::new()->create([
            'methods_services_id' => $service->id,
            'methods_units_id' => $unit->id,
            'ordre' => 20,
            'seting_time' => 0,
            'unit_time' => 1,
            'qty' => 2,
        ]);

        $calculator = new TaskDateCalculator();
        // Lundi 6 mai 2024 08:00 — début de journée ouvrée.
        $anchor = Carbon::create(2024, 5, 6, 8, 0, 0);

        $chained = $calculator->chainTasksForward(collect([$upstream, $downstream]), $anchor);

        $this->assertCount(2, $chained);
        // Upstream : 08:00 → 10:00
        $this->assertSame('2024-05-06 08:00:00', $chained[0]['start']->format('Y-m-d H:i:s'));
        $this->assertSame('2024-05-06 10:00:00', $chained[0]['end']->format('Y-m-d H:i:s'));
        // Downstream : 10:00 → 12:00 (bord à bord, pas de délai)
        $this->assertSame('2024-05-06 10:00:00', $chained[1]['start']->format('Y-m-d H:i:s'));
        $this->assertSame('2024-05-06 12:00:00', $chained[1]['end']->format('Y-m-d H:i:s'));
    }

    public function test_chain_tasks_forward_applies_default_inter_operation_delay(): void
    {
        config()->set('planning.inter_operation_hours', 1);
        $service = MethodsServicesFactory::new()->create();
        $unit = MethodsUnitsFactory::new()->create();

        $upstream = TaskTestFactory::new()->create([
            'methods_services_id' => $service->id, 'methods_units_id' => $unit->id,
            'ordre' => 10, 'seting_time' => 0, 'unit_time' => 1, 'qty' => 2,
        ]);
        $downstream = TaskTestFactory::new()->create([
            'methods_services_id' => $service->id, 'methods_units_id' => $unit->id,
            'ordre' => 20, 'seting_time' => 0, 'unit_time' => 1, 'qty' => 2,
        ]);

        $resolver = new InterOperationDelayResolver();
        $calculator = new TaskDateCalculator();
        $anchor = Carbon::create(2024, 5, 6, 8, 0, 0);

        $chained = $calculator->chainTasksForward(
            collect([$upstream, $downstream]),
            $anchor,
            fn (?int $from, ?int $to) => $resolver->hoursBetween($from, $to),
        );

        // Upstream inchangé : 08:00 → 10:00
        $this->assertSame('2024-05-06 10:00:00', $chained[0]['end']->format('Y-m-d H:i:s'));
        // Downstream décalé d'1h vers l'aval : start = 11:00, end = 13:00
        $this->assertSame('2024-05-06 11:00:00', $chained[1]['start']->format('Y-m-d H:i:s'));
        $this->assertSame('2024-05-06 13:00:00', $chained[1]['end']->format('Y-m-d H:i:s'));
    }

    public function test_chain_tasks_forward_skips_weekend_when_anchor_falls_on_saturday(): void
    {
        // Ancrer un samedi doit recaler au lundi ouvré suivant, symétrique à
        // adjustForWeekendsAndHolidays côté ALAP.
        $service = MethodsServicesFactory::new()->create();
        $unit = MethodsUnitsFactory::new()->create();

        $task = TaskTestFactory::new()->create([
            'methods_services_id' => $service->id, 'methods_units_id' => $unit->id,
            'ordre' => 10, 'seting_time' => 0, 'unit_time' => 1, 'qty' => 2,
        ]);

        $calculator = new TaskDateCalculator();
        $anchor = Carbon::create(2024, 5, 4, 8, 0, 0); // samedi

        $chained = $calculator->chainTasksForward(collect([$task]), $anchor);

        // Recalé au lundi 6 mai : 08:00 → 10:00
        $this->assertSame('2024-05-06 08:00:00', $chained[0]['start']->format('Y-m-d H:i:s'));
        $this->assertSame('2024-05-06 10:00:00', $chained[0]['end']->format('Y-m-d H:i:s'));
    }

    public function test_selects_resource_respecting_capacity(): void
    {
        $service = MethodsServicesFactory::new()->create();
        $unit = MethodsUnitsFactory::new()->create();
        $task = TaskTestFactory::new()->create([
            'methods_services_id' => $service->id,
            'methods_units_id' => $unit->id,
            'qty' => 1,
            'seting_time' => 0,
            'unit_time' => 1,
            'qty_init' => 1,
        ]);

        $insufficient = MethodsRessourcesFactory::new()->create([
            'methods_services_id' => $service->id,
            'capacity' => 0,
            'label' => 'R1',
        ]);
        $sufficient = MethodsRessourcesFactory::new()->create([
            'methods_services_id' => $service->id,
            'capacity' => 2,
            'label' => 'R2',
        ]);

        $calculator = new TaskDateCalculator();
        $selected = $calculator->selectResourceForTask($task, collect([$insufficient, $sufficient]));

        $this->assertSame('R2', $selected->label);
    }
}

class MethodsServicesFactory extends Factory
{
    protected $model = MethodsServices::class;

    public function definition(): array
    {
        return [
            'code' => 'SVC',
            'ordre' => 1,
            'label' => 'Service',
            'type' => 1,
            'hourly_rate' => 10,
            'margin' => 0,
            'color' => '#000',
            'picture' => '',
            'companies_id' => 1,
        ];
    }
}

class MethodsUnitsFactory extends Factory
{
    protected $model = MethodsUnits::class;

    public function definition(): array
    {
        return [
            'code' => 'U',
            'label' => 'Unit',
            'type' => 'qty',
            'default' => true,
        ];
    }
}

class TaskTestFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'label' => 'Task',
            'ordre' => 1,
            'methods_services_id' => null,
            'methods_units_id' => null,
            'seting_time' => 1,
            'unit_time' => 1,
            'qty' => 1,
            'qty_init' => 1,
            'type' => 1,
            'status_id' => 1,
        ];
    }
}

class MethodsRessourcesFactory extends Factory
{
    protected $model = MethodsRessources::class;

    public function definition(): array
    {
        return [
            'ordre' => 1,
            'code' => 'RES',
            'label' => 'Resource',
            'capacity' => 1,
            'methods_services_id' => null,
            'section_id' => 1,
            'color' => '#000000',
        ];
    }
}
