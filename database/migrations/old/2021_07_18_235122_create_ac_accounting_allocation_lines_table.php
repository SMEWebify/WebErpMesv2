<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAcAccountingAllocationLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ac_accounting_allocation_lines', function(Blueprint $table)
		{
			$table->id();
			$table->integer('ARTICLE_ID');
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
		Schema::drop('ac_accounting_allocation_lines');
	}

}
