<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePurchaseOrderTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('purchase_order', function(Blueprint $table)
		{
			$table->id();
			$table->string('CODE');
			$table->string('INDICE');
			$table->string('LABEL');
			$table->string('LABEL_INDICE');
			$table->integer('COMPANY_ID');
			$table->integer('CONTACT_ID');
			$table->integer('ADRESSE_ID');
			$table->date('DATE_REQUIREMENT');
			$table->integer('ETAT');
			$table->integer('CREATOR_ID');
			$table->integer('MODIFIED_ID')->nullable();
			$table->integer('BUYER_ID');
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
		Schema::drop('purchase_order');
	}

}
