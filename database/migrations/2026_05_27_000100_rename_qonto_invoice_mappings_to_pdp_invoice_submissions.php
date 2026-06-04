<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Généralise le suivi des dépôts de factures à toute PDP :
 *   - table  qonto_invoice_mappings  → pdp_invoice_submissions
 *   - colonne qonto_invoice_id        → external_id
 *   - ajout d'une colonne provider (défaut 'qonto' pour les lignes existantes)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('qonto_invoice_mappings', 'pdp_invoice_submissions');

        Schema::table('pdp_invoice_submissions', function (Blueprint $table) {
            $table->renameColumn('qonto_invoice_id', 'external_id');
        });

        Schema::table('pdp_invoice_submissions', function (Blueprint $table) {
            $table->string('provider')->default('qonto')->after('invoice_id');
        });

        Schema::table('pdp_invoice_submissions', function (Blueprint $table) {
            $table->index(['provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('pdp_invoice_submissions', function (Blueprint $table) {
            $table->dropIndex(['provider', 'external_id']);
            $table->dropColumn('provider');
        });

        Schema::table('pdp_invoice_submissions', function (Blueprint $table) {
            $table->renameColumn('external_id', 'qonto_invoice_id');
        });

        Schema::rename('pdp_invoice_submissions', 'qonto_invoice_mappings');
    }
};
