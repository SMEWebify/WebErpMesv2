<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products_quantity_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('products_id');
            $table->unsignedBigInteger('companies_id');
            $table->unsignedInteger('min_qty');
            $table->unsignedInteger('max_qty')->nullable();
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->foreign('products_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_quantity_prices');
    }
};
