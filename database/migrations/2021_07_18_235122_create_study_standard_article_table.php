<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateStudyStandardArticleTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('study_standard_article', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('CODE');
			$table->string('LABEL');
			$table->string('IND');
			$table->integer('PRESTATION_ID');
			$table->integer('FAMILLE_ID');
			$table->integer('ACHETER');
			$table->decimal('PRIX_ACHETER', 10, 3);
			$table->integer('VENDU');
			$table->decimal('PRIX_VENDU', 10, 3);
			$table->integer('UNITE_ID');
			$table->string('MATIERE');
			$table->decimal('EP', 10, 3);
			$table->decimal('DIM_X', 10, 3);
			$table->decimal('DIM_Y', 10, 3);
			$table->decimal('DIM_Z', 10, 3);
			$table->decimal('POIDS', 10, 3);
			$table->decimal('SUR_X', 10, 3);
			$table->decimal('SUR_Y', 10, 3);
			$table->decimal('SUR_Z', 10, 3);
			$table->text('COMMENT', 65535);
			$table->string('IMAGE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('study_standard_article');
	}

}
