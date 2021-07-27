<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMethodsResourceTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('methods_resource', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('CODE');
			$table->string('LABEL');
			$table->string('IMAGE')->nullable();
			$table->integer('MASK_TIME');
			$table->integer('ORDRE');
			$table->decimal('CAPACITY', 11, 0);
			$table->integer('SECTION_ID');
			$table->string('COLOR');
			$table->string('PRESTATION_ID');
			$table->text('COMMENT', 65535)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('methods_resource');
	}

}
