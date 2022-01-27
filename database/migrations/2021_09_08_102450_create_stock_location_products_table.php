<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockLocationProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_location_products', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->integer('user_id'); 
            $table->integer('stock_locations_id');
            $table->integer('products_id');
            $table->decimal('stock_qty', 11, 3)->default(0);
            $table->decimal('reserve_qty', 11, 3)->default(0);
            $table->decimal('mini_qty', 11, 3)->default(0);
            $table->date('end_date')->nullable();
            $table->string('addressing')->nullable();
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
        Schema::dropIfExists('stock_location_products');
    }
}
