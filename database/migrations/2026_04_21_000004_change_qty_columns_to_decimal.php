<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // order_lines : toutes les colonnes qty sont en integer, doivent accepter des décimales
        // (ex. : vente au kg, au mètre, à la feuille avec chute partielle)
        Schema::table('order_lines', function (Blueprint $table) {
            $table->decimal('qty',                      10, 3)->change();
            $table->decimal('delivered_qty',            10, 3)->change();
            $table->decimal('delivered_remaining_qty',  10, 3)->change();
            $table->decimal('invoiced_qty',             10, 3)->change();
            $table->decimal('invoiced_remaining_qty',   10, 3)->change();
        });

        Schema::table('quote_lines', function (Blueprint $table) {
            $table->decimal('qty', 10, 3)->change();
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->decimal('qty', 10, 3)->change();
        });

        Schema::table('credit_note_lines', function (Blueprint $table) {
            $table->decimal('qty', 10, 3)->change();
        });

        Schema::table('stock_moves', function (Blueprint $table) {
            $table->decimal('qty', 10, 3)->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->integer('qty')->change();
            $table->integer('delivered_qty')->change();
            $table->integer('delivered_remaining_qty')->change();
            $table->integer('invoiced_qty')->change();
            $table->integer('invoiced_remaining_qty')->change();
        });

        Schema::table('quote_lines', function (Blueprint $table) {
            $table->integer('qty')->change();
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->integer('qty')->change();
        });

        Schema::table('credit_note_lines', function (Blueprint $table) {
            $table->integer('qty')->change();
        });

        Schema::table('stock_moves', function (Blueprint $table) {
            $table->integer('qty')->change();
        });
    }
};
