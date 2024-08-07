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
			$table->unsignedBigInteger('companies_id');
			$table->unsignedBigInteger('companies_contacts_id');
			$table->unsignedBigInteger('companies_addresses_id');
			$table->date('validity_date')->nullable();
			$table->integer('statu')->default(1);
            #1 = Open
            #2 = Send
            #3 = Win
            #4 = Lost
            #5 = Closed
            #6 = Obsolete
			$table->unsignedBigInteger('user_id');
			$table->unsignedBigInteger('opportunities_id')->nullable();
			$table->unsignedBigInteger('accounting_payment_conditions_id');
			$table->unsignedBigInteger('accounting_payment_methods_id');
			$table->unsignedBigInteger('accounting_deliveries_id');
			$table->text('comment')->nullable();
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('companies_contacts_id')->references('id')->on('companies_contacts')->onDelete('cascade');
            $table->foreign('companies_addresses_id')->references('id')->on('companies_addresses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
