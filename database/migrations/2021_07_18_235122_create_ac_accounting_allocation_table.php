<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAcAccountingAllocationTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ac_accounting_allocation', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('CODE');
			$table->string('LABEL');
			$table->integer('TVA');
			$table->integer('COMPTE_TVA');
			$table->integer('CODE_COMPTA');
			$table->integer('TYPE_IMPUTATION');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ac_accounting_allocation');
	}

}
