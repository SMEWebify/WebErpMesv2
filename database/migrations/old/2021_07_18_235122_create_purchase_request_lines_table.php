<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePurchaseRequestLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('purchase_request_lines', function(Blueprint $table)
		{
			$table->id();
			$table->integer('PURCHASE_REQUEST_ID');
			$table->integer('TASK_ID')->nullable();
			$table->integer('ARTICLE_ID')->nullable();
			$table->integer('ORDRE');
			$table->string('LABEL');
			$table->string('TECHNICAL_SPECIFICATION');
			$table->integer('QT');
			$table->integer('UNIT_ID');
			$table->decimal('PRIX_U', 10, 3);
			$table->decimal('DISCOUNT', 10, 3);
			$table->integer('ETAT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('purchase_request_lines');
	}

}
