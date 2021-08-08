<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTimeImproductiveActivityTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('time_improductive_activity', function(Blueprint $table)
		{
			$table->id();
			$table->string('LABEL')->nullable();
			$table->integer('ETAT_MACHINE');
			$table->integer('RESSOURCE_NEC');
			$table->integer('MASK_TIME');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('time_improductive_activity');
	}

}
