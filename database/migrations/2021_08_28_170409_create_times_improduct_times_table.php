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
			$table->string('label')->nullable();
			$table->integer('times_machine_events_id');
			$table->integer('resources_required')->nullable();
			$table->integer('mask_time')->nullable();
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
