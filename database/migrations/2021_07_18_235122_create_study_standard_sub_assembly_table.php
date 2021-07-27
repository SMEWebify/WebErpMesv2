<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateStudyStandardSubAssemblyTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('study_standard_sub_assembly', function(Blueprint $table)
		{
			$table->integer('id', true);
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
		Schema::drop('study_standard_sub_assembly');
	}

}
