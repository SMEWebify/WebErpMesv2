<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateQuoteTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quote', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('CODE');
			$table->string('INDICE');
			$table->string('LABEL');
			$table->string('LABEL_INDICE');
			$table->integer('COMPANY_ID');
			$table->integer('CONTACT_ID');
			$table->integer('ADRESSE_ID');
			$table->integer('FACTURATION_ID');
			$table->date('DATE_VALIDITE');
			$table->integer('ETAT');
			$table->integer('CREATOR_ID');
			$table->integer('MODIFIED_ID')->nullable();
			$table->integer('RESP_COM_ID');
			$table->integer('RESP_TECH_ID');
			$table->string('REFERENCE');
			$table->integer('COND_REG_COMPANY_ID');
			$table->integer('MODE_REG_COMPANY_ID');
			$table->integer('ECHEANCIER_ID');
			$table->integer('TRANSPORT_ID');
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
		Schema::drop('quote');
	}

}
