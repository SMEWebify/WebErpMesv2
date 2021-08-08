<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateStockLocationTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('stock_location', function(Blueprint $table)
		{
			$table->id();
			$table->integer('CREATOR_ID');
			$table->integer('MODIFIED_ID')->nullable();
			$table->string('CODE');
			$table->string('LABEL')->nullable();
			$table->text('COMMENT', 65535)->nullable();
			$table->integer('STOCK_ID');
			$table->date('END_DATE')->nullable();
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
		Schema::drop('stock_location');
	}

}
