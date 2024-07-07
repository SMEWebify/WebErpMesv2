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
			$table->string('code');
			$table->string('label');
			$table->string('website')->nullable();
			$table->string('fbsite')->nullable();
			$table->string('twittersite')->nullable();
			$table->string('lkdsite')->nullable();
			$table->string('siren')->nullable();
			$table->string('naf_code')->nullable();
			$table->string('intra_community_vat')->nullable();
			$table->string('picture')->nullable();
			$table->integer('statu_customer')->default(1);
            #1 - Inactive
            #2 - Active
            #3 - Prospect
			$table->integer('discount')->nullable()->default(0);
			$table->integer('user_id')->nullable()->default(0);
			$table->integer('account_general_customer')->nullable()->default(0);
			$table->integer('account_auxiliary_customer')->nullable()->default(0);
			$table->integer('statu_supplier')->default(1);
            #1 - Inactive
            #2 - Active
			$table->integer('account_general_supplier')->nullable()->default(0);
			$table->integer('account_auxiliary_supplier')->nullable()->default(0);
			$table->integer('recept_controle')->default(0);
			$table->text('comment')->nullable();
			$table->string('sector_id')->nullable();
            $table->boolean('active')->default(1);
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
