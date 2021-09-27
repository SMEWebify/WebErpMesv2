<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('factory', function (Blueprint $table) {
            $table->id();
            $table->string('NAME');
			$table->string('ADDRESS');
			$table->string('CITY');
			$table->string('ZIPCODE');
			$table->string('REGION')->nullable();
			$table->string('COUNTRY')->nullable();
			$table->string('PHONE_NUMBER')->nullable();
			$table->string('MAIL')->nullable();
			$table->string('WEB_SITE');
			$table->string('PICTURE');
			$table->string('SIREN')->nullable();
			$table->string('nat_regis_num')->nullable();
			$table->string('vat_num');
			$table->integer('vat_id');
			$table->string('share_capital')->nullable();
            $table->string('curency')->nullable();
            $table->string('add_day_validity_quote')->nullable();
            $table->string('add_delivery_delay_order')->nullable();
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
        Schema::dropIfExists('factories');
    }
}
