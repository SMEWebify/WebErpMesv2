<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
			$table->id();
			$table->integer('companies_id');
			$table->integer('ORDRE');
			$table->string('LABEL');
			$table->string('ADRESS')->nullable();
			$table->string('ZIPCODE')->nullable();
			$table->string('CITY')->nullable();
			$table->string('COUNTRY')->nullable();
			$table->string('NUMBER')->nullable();
			$table->string('MAIL')->nullable();
			$table->timestamps();

			$table->foreign('companies_id')->constrained();
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
