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
        Schema::create('customer_price_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('products_id');
            $table->unsignedBigInteger('companies_id')->nullable();
            $table->unsignedTinyInteger('customer_type')->nullable();
            $table->unsignedInteger('min_qty')->default(1);
            $table->unsignedInteger('max_qty')->nullable();
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->foreign('products_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique([
                'products_id',
                'companies_id',
                'customer_type',
                'min_qty',
                'max_qty',
            ], 'customer_price_lists_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_price_lists');
    }
};
