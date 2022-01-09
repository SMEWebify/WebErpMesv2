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
            $table->string('CODE');
			$table->string('LABEL');
			$table->integer('companies_id');
			$table->integer('companies_contacts_id');
			$table->integer('companies_addresses_id');
			$table->integer('statu')->default(1);
            $table->integer('invoice_type')->default(1);
            $table->integer('accounting_status')->default(1);
			$table->integer('user_id');
            $table->integer('bank_id')->nullable();
			$table->text('comment', 65535)->nullable();
            $table->integer('order_id')->nullable();
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
        Schema::dropIfExists('invoices');
    }
}
