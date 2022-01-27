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
			$table->integer('ordre');
			$table->string('civility');
			$table->string('first_name');
			$table->string('name');
			$table->string('function')->nullable();
			$table->string('number')->nullable();
			$table->string('mobile')->nullable();
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
		Schema::drop('companies_contacts');
	}

}
