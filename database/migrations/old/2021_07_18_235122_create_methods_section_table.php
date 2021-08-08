<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMethodsSectionTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('methods_section', function(Blueprint $table)
		{
			$table->id();
			$table->integer('ORDRE');
			$table->string('CODE');
			$table->string('LABEL');
			$table->integer('COUT_H');
			$table->integer('RESPONSABLE');
			$table->string('COLOR');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('methods_section');
	}

}
