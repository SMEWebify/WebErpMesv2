<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indice du plan porté par les lignes (et non plus seulement par l'article).
 *
 * Colonne `ind`, comme `products.ind` : texte libre, facultatif, sans défaut.
 * Migration purement additive : `null` = aucun indice saisi = comportement actuel.
 * (Pas `revision` : ce nom est déjà celui de l'indice de révision d'un ARC.)
 */
return new class extends Migration
{
    private const TABLES = [
        'quote_lines',
        'order_lines',
        'order_confirmation_lines',
        'delivery_lines',
        'invoice_lines',
        'credit_note_lines',
        'purchase_lines',
        'purchase_quotation_lines',
        'quality_non_conformities',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'ind')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('ind', 50)->nullable();
                });
            }
        }

        // Repère des alertes de cohérence (lot suivant) : lignes ouvertes d'un même article.
        Schema::table('order_lines', function (Blueprint $t) {
            $t->index('ind', 'order_lines_ind_index');
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $t) {
            $t->dropIndex('order_lines_ind_index');
        });

        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'ind')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('ind');
                });
            }
        }
    }
};