<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAcTimelinePaiementLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ac_timeline_paiement_lines', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('ECHEANCIER_ID');
			$table->string('LABEL');
			$table->decimal('POURC_MONTANT', 10, 3);
			$table->decimal('POURC_TVA', 10, 3);
			$table->integer('CONDI_REG_ID');
			$table->integer('MODE_REG_ID');
			$table->integer('DELAI');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ac_timeline_paiement_lines');
	}

}
