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
        Schema::create('credit_note_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_note_id');
            $table->unsignedBigInteger('order_line_id');
            $table->unsignedBigInteger('invoice_line_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('qty');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('credit_note_id')->references('id')->on('credit_notes')->onDelete('cascade');
            $table->foreign('order_line_id')->references('id')->on('order_lines')->onDelete('cascade');
            $table->foreign('invoice_line_id')->references('id')->on('invoice_lines')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_note_lines');
    }
};
