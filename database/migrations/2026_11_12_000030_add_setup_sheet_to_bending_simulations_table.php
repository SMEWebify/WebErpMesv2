<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fiche de réglage PDF de NEST2BEND (champ `fiche` de GET /api/v1/results/{id}).
 *
 * - `fiche_url` : URL absolue du PDF, lue avec la clé API côté serveur seulement. Gardée
 *   pour « Récupérer la fiche » (le résultat reste lisible 30 jours chez NEST2BEND).
 * - `fiche_status` : null (pas de fiche) | pending | stored | failed.
 * - `fiche_file_id` : PDF rangé dans la GED de la ligne (version courante au moment du rangement).
 * - `simulation` : presse, montages et ordre de pliage (`resultat.simulation`), pour l'affichage.
 *
 * Additive : colonnes nullables seulement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bending_simulations', function (Blueprint $table) {
            $table->string('fiche_url', 2048)->nullable()->after('section');
            $table->string('fiche_status', 16)->nullable()->after('fiche_url');
            $table->unsignedBigInteger('fiche_file_id')->nullable()->after('fiche_status');
            // Dernier statut HTTP de l'échec (404, 503…), 0 pour un timeout ; jamais de contenu.
            $table->unsignedSmallInteger('fiche_error')->nullable()->after('fiche_file_id');
            $table->json('simulation')->nullable()->after('fiche_error');
        });
    }

    public function down(): void
    {
        Schema::table('bending_simulations', function (Blueprint $table) {
            $table->dropColumn(['fiche_url', 'fiche_status', 'fiche_file_id', 'fiche_error', 'simulation']);
        });
    }
};
