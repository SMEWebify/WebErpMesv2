<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTimeDailyHourlyModelLineTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('time_daily_hourly_model_line', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('MODEL_ID');
			$table->integer('ORDRE');
			$table->integer('TYPE');
			$table->time('START');
			$table->time('END');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('time_daily_hourly_model_line');
	}

}
