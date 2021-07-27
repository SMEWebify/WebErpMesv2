<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateQlNfcTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ql_nfc', function(Blueprint $table)
		{
			$table->integer('ID', true);
			$table->string('CODE');
			$table->string('LABEL');
			$table->integer('ETAT');
			$table->integer('TYPE');
			$table->integer('CREATOR_ID');
			$table->integer('MODIFIED_ID');
			$table->integer('CAUSED_BY_ID');
			$table->integer('SECTION_ID');
			$table->integer('RESSOURCE_ID');
			$table->integer('DEFAUT_ID');
			$table->text('DEFAUT_COMMENT', 65535);
			$table->integer('CAUSE_ID');
			$table->text('CAUSE_COMMENT', 65535);
			$table->integer('CORRECTION_ID');
			$table->text('CORRECTION_COMMENT', 65535);
			$table->text('COMMENT', 65535);
			$table->integer('COMPANY_ID');
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
		Schema::drop('ql_nfc');
	}

}
