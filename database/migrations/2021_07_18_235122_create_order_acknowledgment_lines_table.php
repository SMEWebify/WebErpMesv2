<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOrderAcknowledgmentLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('order_acknowledgment_lines', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('ORDER_ACKNOWLEGMENT_ID');
			$table->integer('ORDER_ID');
			$table->integer('ORDRE');
			$table->integer('ORDER_LINE_ID');
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
		Schema::drop('order_acknowledgment_lines');
	}

}
