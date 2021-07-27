<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePurchaseDeliveryNoteTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('purchase_delivery_note', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('CODE');
			$table->string('INDICE');
			$table->string('LABEL');
			$table->string('LABEL_INDICE');
			$table->integer('COMPANY_ID');
			$table->integer('CONTACT_ID');
			$table->integer('ADRESSE_ID');
			$table->integer('ETAT');
			$table->integer('CREATOR_ID');
			$table->integer('MODIFIED_ID')->nullable();
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
		Schema::drop('purchase_delivery_note');
	}

}
