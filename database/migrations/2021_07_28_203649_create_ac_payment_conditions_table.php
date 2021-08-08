<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcPaymentConditionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ac_payment_conditions', function(Blueprint $table)
		{
			$table->id();
			$table->string('CODE');
			$table->string('LABEL');
			$table->integer('NUMBER_OF_MONTH');
			$table->integer('NUMBER_OF_DAY');
			$table->integer('MONTH_END');

            $table->foreign('id')->constrained();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ac_payment_conditions');
	}

}
