<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dossier de fin de fabrication, par ligne de commande : tant qu'il n'est pas
 * validé, une ligne qui l'exige ne passe ni sur un BL ni sur une facture
 * (ManufacturingDossierGate).
 *
 * Purement additive : `dossier_required` vaut faux pour toutes les lignes
 * existantes, donc rien de ce qui est déjà en cours n'est bloqué. L'exigence
 * vient de la catégorie d'affaire (`requires_manufacturing_dossier`, faux par
 * défaut) ou d'un réglage ligne par ligne sur la fiche commande.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('order_lines', 'dossier_required')) {
                $table->boolean('dossier_required')->default(false);
            }
            if (! Schema::hasColumn('order_lines', 'dossier_validated_at')) {
                $table->timestamp('dossier_validated_at')->nullable();
            }
            if (! Schema::hasColumn('order_lines', 'dossier_validated_by')) {
                $table->foreignId('dossier_validated_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('order_lines', 'dossier_source')) {
                // manual | siteflow (ManufacturingDossierGate::SOURCES)
                $table->string('dossier_source', 20)->nullable();
            }
        });

        Schema::table('affair_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('affair_categories', 'requires_manufacturing_dossier')) {
                $table->boolean('requires_manufacturing_dossier')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropForeign(['dossier_validated_by']);
            $table->dropColumn(['dossier_required', 'dossier_validated_at', 'dossier_validated_by', 'dossier_source']);
        });

        Schema::table('affair_categories', function (Blueprint $table) {
            $table->dropColumn('requires_manufacturing_dossier');
        });
    }
};
