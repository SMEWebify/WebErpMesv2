<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_receipt_lines', function (Blueprint $table) {
            // Correction des types : integer signé → unsignedBigInteger pour cohérence
            // avec les PK des tables référencées (bigIncrements).
            // Sans cette correction, MySQL refuse les contraintes FK (type mismatch).
            $table->unsignedBigInteger('purchase_receipt_id')->change();
            $table->unsignedBigInteger('purchase_line_id')->change();
            $table->unsignedBigInteger('stock_location_products_id')->nullable()->change();

            // receipt_qty manquait dans la migration qty→decimal (migration 000004)
            $table->decimal('receipt_qty', 10, 3)->default(0)->change();

            $table->foreign('purchase_receipt_id')
                  ->references('id')->on('purchase_receipts')
                  ->onDelete('cascade');

            $table->foreign('purchase_line_id')
                  ->references('id')->on('purchase_lines')
                  ->onDelete('cascade');

            $table->foreign('stock_location_products_id')
                  ->references('id')->on('stock_location_products')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_receipt_lines', function (Blueprint $table) {
            $table->dropForeign(['purchase_receipt_id']);
            $table->dropForeign(['purchase_line_id']);
            $table->dropForeign(['stock_location_products_id']);

            $table->integer('purchase_receipt_id')->change();
            $table->integer('purchase_line_id')->change();
            $table->integer('stock_location_products_id')->nullable()->change();
            $table->integer('receipt_qty')->default(0)->change();
        });
    }
};
