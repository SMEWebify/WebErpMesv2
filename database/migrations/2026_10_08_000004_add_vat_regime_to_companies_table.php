<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Régime de TVA du tiers, saisi dans la fiche société (client ET fournisseur),
 * à côté du numéro de TVA intracommunautaire. Sert d'axe à la matrice de TVA.
 *
 * Deux champs documentaires pour la franchise sur attestation (art. 275 CGI,
 * clients fortement exportateurs) : référence et validité de l'attestation.
 * Le suivi du contingent n'est pas automatisé (contrôle à la main).
 *
 * Nullable : les tiers existants restent sans régime → la résolution retombe
 * sur le code de TVA par défaut (comportement actuel). Rétrocompatible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('vat_regime_id')->nullable()->after('intra_community_vat')
                ->constrained('vat_regimes')->nullOnDelete();
            $table->string('vat_attestation_ref')->nullable()->after('vat_regime_id');
            $table->date('vat_attestation_valid_until')->nullable()->after('vat_attestation_ref');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vat_regime_id');
            $table->dropColumn(['vat_attestation_ref', 'vat_attestation_valid_until']);
        });
    }
};
