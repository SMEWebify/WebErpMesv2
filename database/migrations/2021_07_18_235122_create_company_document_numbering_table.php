<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCompanyDocumentNumberingTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('company_document_numbering', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('DOC_TYPE');
			$table->string('MODEL');
			$table->integer('DIGIT');
			$table->integer('COMPTEUR');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('company_document_numbering');
	}

}
