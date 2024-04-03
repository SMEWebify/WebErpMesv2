<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('code');
			$table->string('label');
			$table->string('customer_reference')->nullable();
			$table->integer('companies_id');
			$table->integer('companies_contacts_id');
			$table->integer('companies_addresses_id');
			$table->date('validity_date')->nullable();
			$table->integer('statu')->default(1);
            #1 = Open
            #2 = Send
            #3 = Win
            #4 = Lost
            #5 = Closed
            #6 = Obsolete
			$table->integer('user_id');
			$table->integer('opportunities_id')->nullable();
			$table->integer('accounting_payment_conditions_id');
			$table->integer('accounting_payment_methods_id');
			$table->integer('accounting_deliveries_id');
			$table->text('comment', 65535)->nullable();
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
        Schema::dropIfExists('quotes');
    }
}
