<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOrdersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('orders', function(Blueprint $table)
		{
			$table->id();
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
			$table->integer('RESP_COM_ID');
			$table->integer('RESP_TECH_ID');
			$table->string('REFERENCE')->nullable();
			$table->integer('COND_REG_COMPANY_ID');
			$table->integer('MODE_REG_COMPANY_ID');
			$table->integer('ECHEANCIER_ID');
			$table->integer('TRANSPORT_ID');
			$table->text('COMENT', 65535)->nullable();
			$table->integer('QUOTE_ID')->nullable();
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
		Schema::drop('orders');
	}

}
