<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_moves', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('qty')->default(0);
            $table->integer('reserve_qty')->default(0);
            $table->integer('bad_qty')->default(0);
            $table->integer('stock_location_products_id');
            $table->integer('order_line_id')->nullable();
            $table->integer('task_id')->nullable();
            $table->integer('purchase_receipt_line_id')->nullable();
            $table->integer('typ_move')->default(5);
            #1 - Inventories
            #2 - Task allocation
            #3 - Purchase order reception
            #4 - Inter-stock mvts
            #5 - Manual Stock reception
            #6 - Manual Stock dispatching
            #7 - Reservation
            #8 = Reservation cancellation
            #9 = Part delivery 
            #10 = In production
            #11 = Reservation of a component in production
            #12 = Manufactured component entry
            #13 = Direct inventory
            $table->decimal('component_price', 10, 3)->default(0);
            $table->integer('company_id')->nullable();
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
        Schema::dropIfExists('stock_moves');
    }
};
