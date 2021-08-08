<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAcPaymentMethodTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ac_payment_method', function(Blueprint $table)
		{
			$table->id();
			$table->string('CODE');
			$table->string('LABEL');
			$table->string('CODE_COMPTABLE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ac_payment_method');
	}

}
