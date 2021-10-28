<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactoryTable extends Migration
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
            $table->string('NAME')->default('Name of site');
			$table->string('ADDRESS')->default('Address');
			$table->string('CITY')->default('City');
			$table->string('ZIPCODE')->default('Zip code');
			$table->string('REGION')->nullable();
			$table->string('COUNTRY')->nullable();
			$table->string('PHONE_NUMBER')->nullable();
			$table->string('MAIL')->nullable();
			$table->string('WEB_SITE')->nullable();
			$table->string('PICTURE')->nullable();
			$table->string('SIREN')->nullable();
			$table->string('nat_regis_num')->nullable();
			$table->string('vat_num')->nullable();
			$table->integer('accounting_vats_id')->default(1);
			$table->string('share_capital')->nullable();
            $table->string('curency')->nullable();
            $table->string('add_day_validity_quote')->nullable()->default(0);
            $table->string('add_delivery_delay_order')->nullable()->default(0);
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
        Schema::dropIfExists('factory');
    }
}
