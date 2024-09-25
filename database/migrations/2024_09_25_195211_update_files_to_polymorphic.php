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
        Schema::table('files', function (Blueprint $table) {
            // Delete columns that are no longer needed
            $table->dropColumn([
                'companies_id', 
                'opportunities_id', 
                'quotes_id', 
                'orders_id', 
                'deliverys_id', 
                'invoices_id', 
                'products_id', 
                'purchases_id', 
                'purchase_receipts_id', 
                'quality_non_conformities_id',
                'stock_move_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            // Return to original columns
            $table->integer('companies_id')->nullable();
            $table->integer('opportunities_id')->nullable();
            $table->integer('quotes_id')->nullable();
            $table->integer('orders_id')->nullable();
            $table->integer('deliverys_id')->nullable();
            $table->integer('invoices_id')->nullable();
            $table->integer('products_id')->nullable();
            $table->integer('purchases_id')->nullable();
            $table->integer('purchase_receipts_id')->nullable();
            $table->integer('quality_non_conformities_id')->nullable();
            $table->integer('stock_move_id')->nullable();
        });
    }
};
