<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTimeEventMachineTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('time_event_machine', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('CODE');
			$table->integer('ORDRE');
			$table->string('LABEL');
			$table->integer('MASK_TIME');
			$table->string('COLOR');
			$table->integer('ETAT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('time_event_machine');
	}

}
