<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOrderDeliveryNoteLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('order_delivery_note_lines', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('ORDER_DELEVERY_NOTE_ID');
			$table->integer('ORDER_ID');
			$table->integer('ORDRE');
			$table->integer('ORDER_LINE_ID');
			$table->decimal('QT', 10, 3);
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
		Schema::drop('order_delivery_note_lines');
	}

}
