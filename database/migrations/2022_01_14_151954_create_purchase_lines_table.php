<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('purchases_id');
            $table->integer('tasks_id');
			$table->integer('ORDRE');
			$table->string('CODE')->nullable();
			$table->string('product_id')->nullable();
			$table->string('LABEL');
            $table->string('supplier_ref')->nullable();
			$table->integer('qty');
			$table->decimal('selling_price', 10, 3);
			$table->decimal('discount', 10, 3);
            $table->decimal('unit_price_after_discount', 10, 3);
            $table->decimal('total_selling_price', 10, 3);
            $table->integer('receipt_qty')->default(0);
            $table->integer('invoiced_qty')->default(0);
			$table->integer('methods_units_id');
            $table->integer('accounting_allocation_id')->nullable();
            $table->integer('stock_location_id')->nullable();
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
        Schema::dropIfExists('purchase_lines');
    }
}
