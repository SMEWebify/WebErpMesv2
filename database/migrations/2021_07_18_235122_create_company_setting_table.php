<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCompanySettingTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('company_setting', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('NAME');
			$table->string('ADDRESS');
			$table->string('CITY');
			$table->string('ZIPCODE');
			$table->string('REGION')->nullable();
			$table->string('COUNTRY')->nullable();
			$table->string('PHONE_NUMBER')->nullable();
			$table->string('MAIL')->nullable();
			$table->string('WEB_SITE');
			$table->string('FACEBOOK_SITE')->nullable();
			$table->string('TWITTER_SITE')->nullable();
			$table->string('LKD_SITE')->nullable();
			$table->string('PICTURE_COMPANY');
			$table->string('SIREN')->nullable();
			$table->string('APE')->nullable();
			$table->string('TVA_INTRA');
			$table->integer('TAUX_TVA');
			$table->string('CAPITAL')->nullable();
			$table->string('RCS')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('company_setting');
	}

}
