<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCompanyRightsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('company_rights', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('RIGHT_NAME');
			$table->string('page_1', 3);
			$table->string('page_2', 3);
			$table->string('page_3', 3);
			$table->string('page_4', 3);
			$table->string('page_5', 3);
			$table->string('page_6', 3);
			$table->string('page_7', 3);
			$table->string('page_8', 3);
			$table->string('page_9', 3);
			$table->string('page_10', 3);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('company_rights');
	}

}
