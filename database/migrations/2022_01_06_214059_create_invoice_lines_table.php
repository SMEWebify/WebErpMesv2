<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoiceLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('invoices_id');
            $table->integer('order_line_id');
            $table->integer('delivery_line_id')->nullable();
            $table->integer('ordre');
			$table->integer('qty');
            $table->integer('accounting_allocation_id')->nullable();
            $table->integer('invoice_status')->default(1);
            #1 = In progress
            #2 = Sent
            #3 = Pending
            #4 = Unpaid
            #5 = Paid
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
        Schema::dropIfExists('invoice_lines');
    }
}
