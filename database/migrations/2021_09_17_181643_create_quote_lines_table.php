<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('quotes_id');
			$table->integer('ordre');
			$table->string('code')->nullable();
			$table->string('product_id')->nullable();
			$table->string('label');
			$table->integer('qty');
			$table->integer('methods_units_id');
			$table->decimal('selling_price', 10, 3);
			$table->decimal('discount', 10, 3)->default(0);
			$table->integer('accounting_vats_id');
			$table->date('delivery_date')->nullable();
			$table->integer('statu')->default(1);
            #1 = Open
            #2 = Send
            #3 = Win
            #4 = Lost
            #5 = Closed
            #6 = Obsolete
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
        Schema::dropIfExists('quote_lines');
    }
}
