<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deliverys_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('deliverys_id');
            $table->integer('order_line_id');
            $table->integer('ORDRE');
			$table->integer('qty');
            $table->integer('statu')->default(1);
            $table->integer('invoice_status')->default(1);
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
        Schema::dropIfExists('deliverys_lines');
    }
}
