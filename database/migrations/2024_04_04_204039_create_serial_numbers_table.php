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
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->integer('products_id')->nullable();
            $table->integer('companies_id')->nullable();
            $table->integer('order_line_id')->nullable(); 
            $table->integer('task_id')->nullable(); 
            $table->integer('purchase_receipt_line_id')->nullable(); 
            $table->string('serial_number')->unique();
            $table->integer('status')->default(1);
            #1 = Undefined
            #2 = Sold
            #3 = Shipped
            #4 = Returned
            #5 = In stock
            $table->text('additional_information')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_numbers');
    }
};
