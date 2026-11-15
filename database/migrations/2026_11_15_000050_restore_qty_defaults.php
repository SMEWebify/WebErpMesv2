<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rétablit le default(0) perdu par 2026_04_21_000004 : `->change()` ne reprend pas les
 * modificateurs non répétés. Invisible en production (MySQL non strict mettait déjà 0) ;
 * une base stricte, dont le SQLite des tests, refusait toute insertion qui omettait ces
 * colonnes. Type et nullabilité inchangés (decimal(10,3) NOT NULL).
 */
return new class extends Migration
{
    private const COLUMNS = [
        'order_lines' => ['delivered_qty', 'delivered_remaining_qty', 'invoiced_qty', 'invoiced_remaining_qty'],
        'stock_moves' => ['qty'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($columns) {
                foreach ($columns as $column) {
                    $t->decimal($column, 10, 3)->default(0)->change();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($columns) {
                foreach ($columns as $column) {
                    $t->decimal($column, 10, 3)->change();
                }
            });
        }
    }
};
