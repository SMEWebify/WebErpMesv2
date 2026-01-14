<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInspectionControlPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inspection_control_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inspection_project_id');
            $table->integer('number');
            $table->string('label');
            $table->string('category')->default('dimension');
            $table->decimal('nominal_value', 12, 4)->nullable();
            $table->decimal('tol_min', 12, 4)->nullable();
            $table->decimal('tol_max', 12, 4)->nullable();
            $table->string('unit')->nullable();
            $table->string('frequency_type')->default('all');
            $table->integer('frequency_value')->nullable();
            $table->integer('plan_page')->nullable();
            $table->string('plan_ref')->nullable();
            $table->string('phase')->nullable();
            $table->string('instrument_type')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['inspection_project_id', 'number']);
            $table->index('is_critical');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inspection_control_points');
    }
}
