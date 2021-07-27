<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOrderRemainingTimeTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('order_remaining_time', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('USER_ID');
			$table->date('DATE');
			$table->time('HOUR');
			$table->decimal('COST', 10, 3);
			$table->integer('TASK_ID');
			$table->integer('QTY');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('order_remaining_time');
	}

}
