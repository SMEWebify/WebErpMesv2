<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTimeBankHolidayTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('time_bank_holiday', function(Blueprint $table)
		{
			$table->id();
			$table->integer('FIXE');
			$table->date('DATE');
			$table->string('LABEL');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('time_bank_holiday');
	}

}
