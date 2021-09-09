<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->id();
			$table->string('CODE');
			$table->string('LABEL');
			$table->integer('stocks_id');
            $table->integer('user_id');
			$table->date('END_DATE')->nullable();
            $table->text('COMMENT', 65535)->nullable();
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
        Schema::dropIfExists('stock_locations');
    }
}
