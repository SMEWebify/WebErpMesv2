<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesContactsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('companies_contacts', function(Blueprint $table)
		{
			$table->id();
			$table->integer('companies_id');
			$table->integer('ORDRE');
			$table->string('CIVILITY');
			$table->string('FIRST_NAME');
			$table->string('NAME');
			$table->string('FUNCTION')->nullable();
			$table->string('NUMBER')->nullable();
			$table->string('MOBILE')->nullable();
			$table->string('MAIL')->nullable();
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
		Schema::drop('companies_contacts');
	}

}
