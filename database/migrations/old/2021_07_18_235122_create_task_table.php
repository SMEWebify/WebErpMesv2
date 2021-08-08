<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTaskTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('task', function(Blueprint $table)
		{
			$table->id();
			$table->string('LABEL');
			$table->integer('ORDER');
			$table->integer('QUOTE_LINE_ID')->nullable();
			$table->integer('ORDER_LINE_ID')->nullable();
			$table->integer('COMPONENT_ID')->nullable();
			$table->integer('SERVICE_ID');
			$table->integer('ARTICLE_ID')->nullable();
			$table->decimal('SETING_TIME', 10, 3)->nullable();
			$table->decimal('UNIT_TIME', 10, 3)->nullable();
			$table->decimal('REMAINING_TIME', 10, 3)->nullable();
			$table->decimal('ADVANCEMENT', 10, 3)->nullable()->default(0.000);
			$table->integer('ETAT')->nullable();
			$table->integer('TYPE');
			$table->date('DELAY')->nullable();
			$table->integer('QTY')->nullable();
			$table->integer('QTY_INIT')->nullable();
			$table->integer('QTY_AVIABLE')->nullable();
			$table->decimal('UNIT_COST', 10, 3);
			$table->decimal('UNIT_PRICE', 10, 3);
			$table->integer('UNIT_ID')->nullable();
			$table->decimal('X_SIZE', 10, 3)->nullable();
			$table->decimal('Y_SIZE', 10, 3)->nullable();
			$table->decimal('Z_SIZE', 10, 3)->nullable();
			$table->decimal('X_OVERSIZE', 10, 3)->nullable();
			$table->decimal('Y_OVERSIZE', 10, 3)->nullable();
			$table->decimal('Z_OVERSIZE', 10, 3)->nullable();
			$table->integer('TO_SCHEDULE')->nullable();
			$table->string('MATERIAL')->nullable();
			$table->decimal('THICKNESS', 10, 3)->nullable();
			$table->decimal('WEIGHT', 10, 3)->nullable();
			$table->integer('CREATOR_ID');
			$table->integer('MODIFIED_ID')->nullable();
			$table->integer('QL_NFC_ID')->nullable();
			$table->integer('TOOL_ID')->nullable();
			$table->integer('START_TIMESTAMPS')->nullable();
			$table->integer('END_TIMESTAMPS')->nullable();
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
		Schema::drop('task');
	}

}
