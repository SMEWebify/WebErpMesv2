<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInspectionMeasureSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inspection_measure_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inspection_project_id');
            $table->string('session_code');
            $table->string('type')->default('lot');
            $table->integer('quantity_to_measure')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('status')->default('open');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['inspection_project_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inspection_measure_sessions');
    }
}
