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
			$table->integer('ordre');
			$table->string('label');
			$table->string('adress')->nullable();
			$table->string('zipcode')->nullable();
			$table->string('city')->nullable();
			$table->string('country')->nullable();
			$table->string('number')->nullable();
			$table->string('mail')->nullable();
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
