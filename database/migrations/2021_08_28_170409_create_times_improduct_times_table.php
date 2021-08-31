<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimesImproductTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('times_improduct_times', function (Blueprint $table) {
            $table->id();
			$table->string('LABEL')->nullable();
			$table->integer('times_machine_events_id');
			$table->integer('RESOURCE_REQUIRED')->nullable();
			$table->integer('MASK_TIME')->nullable();
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
        Schema::dropIfExists('times_improduct_times');
    }
}
