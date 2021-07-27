<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOrdersDeliveryReturnTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('orders_delivery_return', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('CODE');
			$table->string('INDICE')->nullable();
			$table->string('LABEL')->nullable();
			$table->string('LABEL_INDICE')->nullable();
			$table->integer('COMPANY_ID');
			$table->integer('CONTACT_ID');
			$table->integer('ADRESSE_ID');
			$table->integer('FACTURATION_ID');
			$table->integer('ETAT');
			$table->integer('CREATOR_ID');
			$table->integer('MODIFIED_ID')->nullable();
			$table->text('COMENT', 65535)->nullable();
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
		Schema::drop('orders_delivery_return');
	}

}
