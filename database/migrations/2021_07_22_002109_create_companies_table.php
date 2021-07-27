<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
			$table->string('CODE');
			$table->string('LABEL');
			$table->string('WEBSITE')->nullable();
			$table->string('FBSITE')->nullable();
			$table->string('TWITTERSITE')->nullable();
			$table->string('LKDSITE')->nullable();
			$table->string('SIREN')->nullable();
			$table->string('APE')->nullable();
			$table->string('TVA_INTRA');
			$table->integer('TVA_ID');
			$table->string('LOGO')->nullable();
			$table->integer('STATU_CLIENT');
			$table->integer('COND_REG_CLIENT_ID')->nullable();
			$table->integer('MODE_REG_CLIENT_ID')->nullable();
			$table->integer('DISCOUNT')->nullable();
			$table->integer('RESP_COM_ID');
			$table->integer('RESP_TECH_ID');
			$table->integer('COMPTE_GEN_CLIENT')->nullable()->default(0);
			$table->integer('COMPTE_AUX_CLIENT')->nullable()->default(0);
			$table->integer('STATU_FOUR');
			$table->integer('COND_REG_FOUR_ID');
			$table->integer('MODE_REG_FOUR_ID');
			$table->integer('COMPTE_GEN_FOUR')->default(0);
			$table->integer('COMPTE_AUX_FOUR')->default(0);
			$table->integer('CONTROLE_FOUR');
			$table->text('COMMENT', 65535)->nullable();
			$table->string('SECTOR_ID');
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
        Schema::dropIfExists('companies');
    }
}
