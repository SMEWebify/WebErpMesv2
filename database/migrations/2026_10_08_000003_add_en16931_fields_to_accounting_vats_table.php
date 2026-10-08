<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attributs EN 16931 intrinsèques à un code de TVA, requis par la facturation
 * électronique (Factur-X) et absents jusqu'ici :
 *   - en16931_category : catégorie UNCL5305 (S, Z, E, AE, K, G) ;
 *   - exemption_reason_code / _text : motif d'exonération (BT-121 / BT-120),
 *     obligatoire dès que la catégorie n'est pas S ou Z ;
 *   - legal_mention : mention légale imprimée sur la facture.
 *
 * Tous nullable : une install existante garde ses codes TVA tels quels, et
 * FacturXBuilder retombe sur l'ancien comportement (S si taux > 0, sinon Z)
 * tant que la catégorie n'est pas renseignée. Rétrocompatible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_vats', function (Blueprint $table) {
            $table->string('en16931_category', 4)->nullable()->after('rate');
            $table->string('exemption_reason_code')->nullable()->after('en16931_category');
            $table->text('exemption_reason_text')->nullable()->after('exemption_reason_code');
            $table->text('legal_mention')->nullable()->after('exemption_reason_text');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_vats', function (Blueprint $table) {
            $table->dropColumn(['en16931_category', 'exemption_reason_code', 'exemption_reason_text', 'legal_mention']);
        });
    }
};
