<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseQuotationLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('purchases_quotation_id');
            $table->integer('tasks_id');
			$table->integer('ordre');
			$table->decimal('qty_to_order', 10, 3);
			$table->decimal('unit_price', 10, 3);
			$table->decimal('total_price', 10, 3);
            $table->decimal('qty_accepted', 10, 3)->default(0);
            $table->decimal('canceled_qty', 10, 3)->default(0);
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
        Schema::dropIfExists('purchase_quotation_lines');
    }
}
