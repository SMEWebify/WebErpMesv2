<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseReceiptLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('purchase_receipt_id');
            $table->integer('purchase_line_id');
			$table->integer('ordre');
            $table->integer('receipt_qty')->default(0);
            $table->integer('stock_location_products_id')->nullable();
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
        Schema::dropIfExists('purchase_receipt_lines');
    }
}
