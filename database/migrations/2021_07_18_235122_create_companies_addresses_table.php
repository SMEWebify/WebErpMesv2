<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCompaniesAddressesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('companies_addresses', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('ID_COMPANY');
			$table->integer('ORDRE');
			$table->string('LABEL');
			$table->string('ADRESSE')->nullable();
			$table->string('ZIPCODE')->nullable();
			$table->string('CITY')->nullable();
			$table->string('COUNTRY')->nullable();
			$table->string('NUMBER')->nullable();
			$table->string('MAIL')->nullable();
			$table->integer('ADRESS_LIV');
			$table->integer('ADRESS_FAC');
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
		Schema::drop('companies_addresses');
	}

}
