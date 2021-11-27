<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_details', function (Blueprint $table) {
            $table->id();
            $table->integer('inventories_id');
			$table->integer('stock_locations_id');
            $table->decimal('start_qty', 11, 3)->nullable();
            $table->decimal('inv_qty', 11, 3)->nullable();
            $table->decimal('price', 11, 3)->nullable();
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
        Schema::dropIfExists('inventory_details');
    }
}
