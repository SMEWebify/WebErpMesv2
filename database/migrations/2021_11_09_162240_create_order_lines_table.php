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
			$table->integer('ordre');
			$table->string('code')->nullable();
			$table->string('product_id')->nullable();
			$table->string('label');
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
			$table->integer('tasks_status')->default(1);
            #1 = No task
            #2 = Created
            #3 = In progress
            #4 = Finished (all the tasks are finished)
            $table->integer('delivery_status')->default(1);
            #1 = Not delivered
            #2 = Partly delivered
            #3 = Delivered or Stock
            #4 = Delivered without Delevery note
            $table->integer('invoice_status')->default(1);
            #1 = Not invoiced
            #2 = Partly invoiced
            #3 = Invoiced
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
