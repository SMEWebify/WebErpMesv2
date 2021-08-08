<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTimeAbsenceTypeTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('time_absence_type', function(Blueprint $table)
		{
			$table->id();
			$table->string('CODE');
			$table->string('LABEL');
			$table->integer('PAYE');
			$table->string('COLOR');
			$table->integer('TYPE_JOUR');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('time_absence_type');
	}

}
