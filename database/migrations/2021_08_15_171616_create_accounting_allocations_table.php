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
			$table->string('account');
			$table->string('label');
			$table->integer('accounting_vats_id');
			$table->integer('vat_account');
			$table->integer('code_account');
			$table->integer('type_imputation');
            #1 = Sale
            #2 = Purchase
            #3 = Down payment (acount)
            #4 = Tax
            #5 = Purchase (stock)
            #6 = Other

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
