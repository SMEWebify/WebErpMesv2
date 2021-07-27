<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAcVatTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ac_vat', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('CODE');
			$table->string('LABEL');
			$table->decimal('TAUX', 10, 3);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ac_vat');
	}

}
