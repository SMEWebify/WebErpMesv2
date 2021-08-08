<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOrdersLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('orders_lines', function(Blueprint $table)
		{
			$table->id();
			$table->integer('ORDER_ID');
			$table->integer('ORDRE');
			$table->string('ARTICLE_CODE');
			$table->string('LABEL');
			$table->integer('QT');
			$table->integer('DELIVERED_QTY')->nullable()->default(0);
			$table->integer('DELIVERED_REMAINING_QTY')->nullable()->default(0);
			$table->integer('INVOICED_QTY')->nullable()->default(0);
			$table->integer('INVOICED_REMAINING_QTY')->nullable()->default(0);
			$table->integer('UNIT_ID');
			$table->decimal('PRIX_U', 10, 3);
			$table->decimal('DISCOUNT', 10, 3);
			$table->integer('TVA_ID');
			$table->date('DELAIS_INTERNE');
			$table->date('DELAIS');
			$table->integer('ETAT');
			$table->integer('AR');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('orders_lines');
	}

}
