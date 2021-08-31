<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimesMachineEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('times_machine_events', function (Blueprint $table) {
            $table->id();
            $table->string('CODE');
			$table->integer('ORDRE');
			$table->string('LABEL');
			$table->integer('MASK_TIME');
			$table->string('COLOR');
			$table->integer('ETAT');
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
        Schema::dropIfExists('times_machine_events');
    }
}
