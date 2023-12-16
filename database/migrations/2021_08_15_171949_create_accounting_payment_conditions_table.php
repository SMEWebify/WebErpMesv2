<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountingPaymentConditionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounting_payment_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('code');
			$table->string('label');
			$table->integer('number_of_month');
			$table->integer('number_of_day');
			$table->integer('month_end');
            $table->integer('default')->default(0);
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
        Schema::dropIfExists('accounting_payment_conditions');
    }
}
