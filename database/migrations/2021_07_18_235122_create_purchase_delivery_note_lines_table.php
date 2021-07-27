<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePurchaseDeliveryNoteLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('purchase_delivery_note_lines', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('PURCHASE_DELIVERY_ID');
			$table->integer('PURCHASE_ORDER_LINES_ID');
			$table->integer('ORDRE');
			$table->integer('QTY_RECEIPT');
			$table->integer('QTY_TO_RETURN');
			$table->decimal('PRIX_U', 10, 3);
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
		Schema::drop('purchase_delivery_note_lines');
	}

}
