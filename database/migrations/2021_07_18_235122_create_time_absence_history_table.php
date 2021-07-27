<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTimeAbsenceHistoryTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('time_absence_history', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('USER_ID');
			$table->integer('absence_type_ID');
			$table->date('START_DATE');
			$table->date('END_DATE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('time_absence_history');
	}

}
