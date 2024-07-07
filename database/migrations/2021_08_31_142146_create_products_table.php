<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
			$table->string('code');
			$table->string('label');
			$table->string('ind')->nullable();
			$table->integer('methods_services_id');
			$table->integer('methods_families_id');
			$table->integer('purchased');
			$table->decimal('purchased_price', 10, 3)->nullable();
			$table->integer('sold');
			$table->decimal('selling_price', 10, 3)->nullable();
			$table->integer('methods_units_id');
			$table->string('material')->nullable();
			$table->decimal('thickness', 10, 3)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
			$table->decimal('x_size', 10, 3)->nullable();
			$table->decimal('y_size', 10, 3)->nullable();
			$table->decimal('z_size', 10, 3)->nullable();
			$table->decimal('x_oversize', 10, 3)->nullable();
			$table->decimal('y_oversize', 10, 3)->nullable();
			$table->decimal('z_oversize', 10, 3)->nullable();
			$table->text('comment')->nullable();
            $table->integer('tracability_type')->default(1);
            $table->decimal('qty_eco_min', 10, 3)->nullable();
			$table->decimal('qty_eco_max', 10, 3)->nullable();
            $table->decimal('diameter', 10, 3)->nullable();
            $table->decimal('diameter_oversize', 10, 3)->nullable();
            $table->decimal('section_size', 10, 3)->nullable();
			$table->string('picture')->nullable();
			$table->string('drawing_file')->nullable();
			$table->string('stl_file')->nullable();
			$table->string('svg_file')->nullable();
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
        Schema::dropIfExists('products');
    }
}
