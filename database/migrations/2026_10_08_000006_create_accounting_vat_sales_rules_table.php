<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Matrice de TVA — VENTES. Clé (régime, nature) → code de TVA + comptes.
 *
 * Résout, à la création d'une ligne, le code de TVA par défaut (et donc la
 * catégorie EN 16931 portée par ce code) ainsi que les comptes de vente et de
 * TVA collectée. Ajoute à `accounting_allocations` la dimension « nature » qui
 * lui manque (un seul compte par sens × TVA aujourd'hui) : c'est ce qui permet
 * de séparer, p. ex., la vente d'outillage (707) de l'usinage (701).
 *
 * `restrictOnDelete` sur les trois clés : on ne supprime pas un régime, une
 * nature ni un code de TVA encore référencé par une ligne de matrice.
 *
 * Table vide à la création : sans règle, la résolution retombe sur le code de
 * TVA par défaut (comportement actuel). Rétrocompatible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_vat_sales_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vat_regime_id')->constrained('vat_regimes')->restrictOnDelete();
            $table->foreignId('vat_nature_id')->constrained('vat_natures')->restrictOnDelete();
            $table->foreignId('accounting_vats_id')->constrained('accounting_vats')->restrictOnDelete();
            // Comptes portés par la matrice (classe 7 vente, 4457 TVA collectée).
            $table->string('sales_account')->nullable();
            $table->string('vat_account')->nullable();
            $table->timestamps();

            $table->unique(['vat_regime_id', 'vat_nature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_vat_sales_rules');
    }
};
