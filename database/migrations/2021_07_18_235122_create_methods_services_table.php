<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMethodsServicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('methods_services', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('CODE');
			$table->integer('ORDRE');
			$table->string('LABEL');
			$table->integer('TYPE');
			$table->integer('TAUX_H');
			$table->integer('MARGE');
			$table->string('COLOR');
			$table->string('IMAGE');
			$table->string('PROVIDER_ID');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('methods_services');
	}

}
