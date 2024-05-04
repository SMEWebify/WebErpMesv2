<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('methods_standard_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('label');
			$table->integer('ordre');
			$table->foreignId('methods_nomenclature_standard_id')->constrained('methods_standard_nomenclatures');
			$table->integer('sub_assembly_id')->nullable();
			$table->integer('methods_services_id');
			$table->integer('component_id')->nullable(); // use for BOM link with product id
			$table->decimal('seting_time', 10, 3)->nullable();
			$table->decimal('unit_time', 10, 3)->nullable();
			$table->integer('type');
            #1 = Productive
            #2 = Raw material
            #3 = Raw material (Sheet)
            #4 = Raw material (Profil)
            #5 = Raw material (block)
            #6 = Supplies
            #6 = Sub-contracting
            #6 = Composed component
			$table->integer('qty')->nullable();
			$table->integer('qty_init')->nullable();
			$table->decimal('unit_cost', 10, 3)->default(0.000);
			$table->decimal('unit_price', 10, 3)->default(0.000);
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
			$table->boolean('not_recalculate')->default(0);
			$table->string('material')->nullable();
			$table->decimal('thickness', 10, 3)->nullable();
			$table->decimal('weight', 10, 3)->nullable();
			$table->integer('methods_tools_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('methods_standard_tasks');
    }
};
