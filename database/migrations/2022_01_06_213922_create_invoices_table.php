<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('code');
			$table->string('label');
			$table->unsignedBigInteger('companies_id');
			$table->unsignedBigInteger('companies_contacts_id');
			$table->unsignedBigInteger('companies_addresses_id');
			$table->integer('statu')->default(1);
            #1 = In progress
            #2 = Sent
            #3 = Pending
            #4 = Unpaid
            #5 = Paid
            $table->integer('invoice_type')->default(1);
            #1 = Invoice
            #2 = Credit note
            #3 = Proforma
            #4 = Down payment
            $table->integer('accounting_status')->default(1);
            #1 = In progress
            #2 = To be posted
            #3 = Posted
			$table->unsignedBigInteger('user_id');
            $table->integer('bank_id')->nullable();
			$table->text('comment')->nullable();
            $table->integer('order_id')->nullable();
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
        Schema::dropIfExists('invoices');
    }
}
