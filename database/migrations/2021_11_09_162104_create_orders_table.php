<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('code');
			$table->string('label');
			$table->string('customer_reference')->nullable();
			$table->unsignedBigInteger('companies_id')->nullable();
			$table->unsignedBigInteger('companies_contacts_id')->nullable();
			$table->unsignedBigInteger('companies_addresses_id')->nullable();
			$table->date('validity_date')->nullable();
			$table->integer('statu')->default(1);
            #1 = Open
            #2 = In progress
            #3 = Delivered
            #4 = Partly delivered
            #5 =  Stopped
            #6 = canceled
			$table->unsignedBigInteger('user_id');
			$table->integer('accounting_payment_conditions_id')->nullable();
			$table->integer('accounting_payment_methods_id')->nullable();
			$table->integer('accounting_deliveries_id')->nullable();
			$table->text('comment')->nullable();
            $table->integer('quotes_id')->nullable();
            $table->integer('type')->default(1);
            #1 = Customer sales order
            #2 = Internal sales order
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
        Schema::dropIfExists('orders');
    }
}
