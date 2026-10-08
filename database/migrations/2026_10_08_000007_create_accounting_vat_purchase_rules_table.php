<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Matrice de TVA — ACHATS. Clé (régime, nature) → code de TVA + comptes.
 *
 * Symétrique de la matrice ventes : compte d'achat (classe 6, ou 2 pour une
 * immobilisation) et TVA déductible. `vat_account_autoliq` = compte de TVA due
 * intracommunautaire, REPÈRE pour la saisie manuelle de l'autoliquidation.
 * `manual_vat` = true : l'écriture TVA (collectée ↔ déductible, net nul des
 * acquisitions UE / import) n'est PAS automatisée ; la ligne est signalée et
 * l'écriture est passée à la main / reprise sur la CA3.
 *
 * Table vide à la création : sans règle, repli sur le code de TVA par défaut.
 * Rétrocompatible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_vat_purchase_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vat_regime_id')->constrained('vat_regimes')->restrictOnDelete();
            $table->foreignId('vat_nature_id')->constrained('vat_natures')->restrictOnDelete();
            $table->foreignId('accounting_vats_id')->constrained('accounting_vats')->restrictOnDelete();
            $table->string('purchase_account')->nullable();
            $table->string('vat_account')->nullable();
            $table->string('vat_account_autoliq')->nullable();
            $table->boolean('manual_vat')->default(false);
            $table->timestamps();

            $table->unique(['vat_regime_id', 'vat_nature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_vat_purchase_rules');
    }
};
