<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateQuoteLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quote_lines', function(Blueprint $table)
		{
			$table->id();
			$table->integer('DEVIS_ID');
			$table->integer('ORDRE');
			$table->string('ARTICLE_CODE');
			$table->string('LABEL');
			$table->integer('QT');
			$table->integer('UNIT_ID');
			$table->decimal('PRIX_U', 10, 3);
			$table->decimal('DISCOUNT', 10, 3);
			$table->integer('TVA_ID');
			$table->date('DELAIS');
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
		Schema::drop('quote_lines');
	}

}
