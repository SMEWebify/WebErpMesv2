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
        Schema::create('sub_assemblies', function (Blueprint $table) {
            $table->id();
            $table->integer('ordre');
			$table->integer('quote_lines_id')->nullable();
			$table->integer('order_lines_id')->nullable();
			$table->integer('products_id')->nullable();
			$table->integer('sub_assembly_id')->nullable();
			$table->integer('child_id');
			$table->integer('qty');
			$table->decimal('unit_price', 10, 3);
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
        Schema::dropIfExists('sub_assemblies');
    }
};
