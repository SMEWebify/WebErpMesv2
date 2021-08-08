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
			$table->string('TVA_INTRA')->nullable();
			$table->integer('TVA_ID')->nullable();
			$table->string('LOGO')->nullable();
			$table->integer('STATU_CLIENT')->default(0);
			$table->integer('DISCOUNT')->nullable()->default(0);
			$table->integer('users_id')->nullable()->default(0);
			$table->integer('COMPTE_GEN_CLIENT')->nullable()->default(0);
			$table->integer('COMPTE_AUX_CLIENT')->nullable()->default(0);
			$table->integer('STATU_FOUR')->default(0);
			$table->integer('COMPTE_GEN_FOUR')->nullable()->default(0);
			$table->integer('COMPTE_AUX_FOUR')->nullable()->default(0);
			$table->integer('RECEPT_CONTROLE')->default(0);
			$table->text('COMMENT', 65535)->nullable();
			$table->string('SECTOR_ID')->nullable();
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
