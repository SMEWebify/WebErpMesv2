<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('label');
			$table->integer('ORDER');
			$table->integer('quote_lines_id')->nullable();
			$table->integer('order_lines_id')->nullable();
			$table->integer('products_id')->nullable();
			$table->integer('methods_services_id');
			$table->integer('component_id')->nullable(); // use for BOM link with product id
			$table->decimal('SETING_TIME', 10, 3)->nullable();
			$table->decimal('UNIT_TIME', 10, 3)->nullable();
			$table->decimal('REMAINING_TIME', 10, 3)->nullable();
			$table->decimal('ADVANCEMENT', 10, 3)->nullable()->default(0.000);
			$table->integer('statu')->default(1);
			$table->integer('TYPE');
			$table->date('DELAY')->nullable();
			$table->integer('qty')->nullable();
			$table->integer('qty_init')->nullable();
			$table->integer('qty_aviable')->nullable();
			$table->decimal('UNIT_COST', 10, 3)->default(0.000);
			$table->decimal('UNIT_PRICE', 10, 3)->default(0.000);
			$table->integer('methods_units_id')->nullable();
			$table->decimal('x_size', 10, 3)->nullable();
			$table->decimal('y_size', 10, 3)->nullable();
			$table->decimal('z_size', 10, 3)->nullable();
			$table->decimal('x_oversize', 10, 3)->nullable();
			$table->decimal('y_oversize', 10, 3)->nullable();
			$table->decimal('z_oversize', 10, 3)->nullable();
            $table->decimal('diameter', 10, 3)->nullable();
			$table->decimal('diameter_oversize', 10, 3)->nullable();
			$table->integer('to_schedule')->nullable();
			$table->string('material')->nullable();
			$table->decimal('thickness', 10, 3)->nullable();
			$table->decimal('weight', 10, 3)->nullable();
			$table->integer('quality_non_conformities_id')->nullable();
			$table->integer('methods_tools_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
