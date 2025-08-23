<?php

namespace Tests\Unit;

use Tests\TestCase;
use Carbon\Carbon;
use App\Services\TaskDateCalculator;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsRessources;
use App\Models\Planning\Task;
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

        Schema::create('methods_units', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('label');
            $table->string('type')->nullable();
            $table->boolean('default')->default(true);
            $table->timestamps();
        });

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

        Schema::create('methods_ressources', function (Blueprint $table) {
            $table->id();
            $table->integer('ordre')->default(1);
            $table->string('code')->nullable();
            $table->string('label');
            $table->integer('capacity')->default(1);
            $table->foreignId('methods_services_id')->nullable();
            $table->timestamps();
        });

        Schema::create('times_banck_holidays', function (Blueprint $table) {
            $table->id();
            $table->boolean('fixed')->default(true);
            $table->date('date');
            $table->string('label');
            $table->timestamps();
        });
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
        ];
    }
}
