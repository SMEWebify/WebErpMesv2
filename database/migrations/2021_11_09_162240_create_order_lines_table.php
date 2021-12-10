<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('orders_id');
			$table->integer('ORDRE');
			$table->string('CODE');
			$table->string('product_id')->nullable();
			$table->string('LABEL')->nullable();
			$table->integer('qty');
            $table->integer('delivered_qty')->default(0);
            $table->integer('delivered_remaining_qty')->default(0);
            $table->integer('invoiced_qty')->default(0);
            $table->integer('invoiced_remaining_qty')->default(0);
			$table->integer('methods_units_id');
			$table->decimal('selling_price', 10, 3);
			$table->decimal('discount', 10, 3);
			$table->integer('accounting_vats_id');
            $table->date('internal_delay')->nullable();
			$table->date('delivery_date')->nullable();
			$table->integer('statu')->default(1);
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
        Schema::dropIfExists('order_lines');
    }
}
