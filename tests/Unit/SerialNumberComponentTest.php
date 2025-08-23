<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Products\SerialNumbers;
use App\Models\Products\SerialNumberComponent;
use App\Models\Planning\Task;

class SerialNumberComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable();
            $table->timestamps();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('products_id')->nullable();
            $table->foreignId('companies_id')->nullable();
            $table->foreignId('order_line_id')->nullable();
            $table->foreignId('task_id')->nullable();
            $table->foreignId('purchase_receipt_line_id')->nullable();
            $table->string('serial_number')->unique();
            $table->integer('status')->default(1);
            $table->text('additional_information')->nullable();
            $table->timestamps();
        });

        Schema::create('serial_number_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_serial_id')->constrained('serial_numbers');
            $table->foreignId('component_serial_id')->constrained('serial_numbers');
            $table->foreignId('task_id')->constrained('tasks');
            $table->timestamps();
            $table->unique('component_serial_id');
        });
    }

    public function test_component_serial_cannot_be_reused_without_movement(): void
    {
        $task = Task::create(['label' => 't']);
        $parent1 = SerialNumbersFactory::new()->create(['task_id' => $task->id]);
        $parent2 = SerialNumbersFactory::new()->create(['task_id' => $task->id]);
        $component = SerialNumbersFactory::new()->create();

        SerialNumberComponent::create([
            'parent_serial_id' => $parent1->id,
            'component_serial_id' => $component->id,
            'task_id' => $task->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        SerialNumberComponent::create([
            'parent_serial_id' => $parent2->id,
            'component_serial_id' => $component->id,
            'task_id' => $task->id,
        ]);
    }
}

class SerialNumbersFactory extends Factory
{
    protected $model = SerialNumbers::class;

    public function definition(): array
    {
        return [
            'serial_number' => $this->faker->uuid,
            'status' => 1,
        ];
    }
}
