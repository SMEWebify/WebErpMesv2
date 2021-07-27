<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCompaniesContactTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('companies_contact', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('ID_COMPANY');
			$table->integer('ORDRE');
			$table->integer('CIVILITE');
			$table->string('PRENOM');
			$table->string('NOM');
			$table->string('FONCTION');
			$table->integer('ADRESSE_ID');
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
		Schema::drop('companies_contact');
	}

}
