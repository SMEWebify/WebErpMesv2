<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOrderSubAssemblyTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('order_sub_assembly', function(Blueprint $table)
		{
			$table->id();
			$table->integer('PARENT_ID');
			$table->integer('ORDRE');
			$table->integer('ARTICLE_ID');
			$table->integer('QT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('order_sub_assembly');
	}

}
