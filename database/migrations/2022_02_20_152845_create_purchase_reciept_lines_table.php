<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseRecieptLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_reciept_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('purchase_reciept_id');
            $table->integer('purchase_lines_id');
			$table->integer('ordre');
            $table->integer('receipt_qty')->default(0);
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
        Schema::dropIfExists('purchase_reciept_lines');
    }
}
