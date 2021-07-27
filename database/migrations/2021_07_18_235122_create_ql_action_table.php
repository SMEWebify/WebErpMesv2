<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateQlActionTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ql_action', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('CODE');
			$table->string('LABEL');
			$table->integer('CREATOR_ID');
			$table->integer('TYPE');
			$table->integer('ETAT');
			$table->integer('RESP_ID');
			$table->text('PB_DESCP', 65535);
			$table->text('CAUSE', 65535);
			$table->text('ACTION', 65535);
			$table->string('COLOR');
			$table->integer('NFC_ID');
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ql_action');
	}

}
