<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInspectionMeasuresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inspection_measures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inspection_measure_session_id');
            $table->unsignedBigInteger('inspection_control_point_id');
            $table->string('serial_number')->nullable();
            $table->decimal('measured_value', 12, 4)->nullable();
            $table->string('result')->default('ok');
            $table->decimal('deviation', 12, 4)->nullable();
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('measured_by');
            $table->timestamp('measured_at');
            $table->unsignedBigInteger('instrument_id')->nullable();
            $table->timestamps();

            $table->index(['inspection_measure_session_id', 'inspection_control_point_id'], 'inspection_measures_session_point');
            $table->index('serial_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inspection_measures');
    }
}
