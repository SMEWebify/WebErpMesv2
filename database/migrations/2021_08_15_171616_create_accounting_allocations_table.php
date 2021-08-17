<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountingAllocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounting_allocations', function (Blueprint $table) {
            $table->id();
			$table->string('ACCOUNT');
			$table->string('LABEL');
			$table->integer('vat_id');
			$table->integer('VAT_ACCOUNT');
			$table->integer('CODE_ACCOUNT');
			$table->integer('TYPE_IMPUTATION');
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
        Schema::dropIfExists('accounting_allocations');
    }
}
