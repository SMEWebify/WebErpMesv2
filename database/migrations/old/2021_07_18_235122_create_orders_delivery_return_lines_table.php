<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOrdersDeliveryReturnLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('orders_delivery_return_lines', function(Blueprint $table)
		{
			$table->id();
			$table->integer('ORDER_DELIVERY_RETURN_ID');
			$table->integer('ORDER_DELEVERY_NOTE_DETAIL_ID');
			$table->decimal('QTY', 10, 3)->nullable();
			$table->decimal('PRICE', 10, 3)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('orders_delivery_return_lines');
	}

}
