<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateToolTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('tool', function(Blueprint $table)
		{
			$table->id();
			$table->string('CODE');
			$table->string('LABEL')->nullable();
			$table->integer('ETAT');
			$table->integer('COST');
			$table->string('PICTURE');
			$table->date('END_DATE')->nullable();
			$table->integer('COMMENT')->nullable();
			$table->integer('QTY');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('tool');
	}

}
