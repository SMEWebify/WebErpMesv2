<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAcAccountingAllocationServicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ac_accounting_allocation_services', function(Blueprint $table)
		{
			$table->integer('ID', true);
			$table->integer('PRESTATION_ID');
			$table->integer('ORDRE');
			$table->integer('IMPUTATION_ID');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ac_accounting_allocation_services');
	}

}
