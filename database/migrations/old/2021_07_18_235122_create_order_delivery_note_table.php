<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOrderDeliveryNoteTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('order_delivery_note', function(Blueprint $table)
		{
			$table->id();
			$table->string('CODE');
			$table->integer('ORDER_ID');
			$table->string('LABEL');
			$table->string('LABEL_INDICE');
			$table->integer('ETAT');
			$table->integer('CREATOR_ID');
			$table->integer('MODIFIED_ID')->nullable();
			$table->integer('INCOTERM');
			$table->text('COMENT', 65535);
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('order_delivery_note');
	}

}
