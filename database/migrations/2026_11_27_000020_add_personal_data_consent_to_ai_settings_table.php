<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Autorisation, donnée par l'entreprise, d'envoyer des données personnelles
 * (noms, e-mails, téléphones de tiers) au fournisseur d'IA configuré. Faux pour
 * l'existant : les fonctions qui en dépendent restent masquées tant qu'elle n'est
 * pas donnée. Qui l'a donnée et quand : trace pour le registre des traitements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->boolean('allow_personal_data')->default(false);
            $table->timestamp('personal_data_allowed_at')->nullable();
            $table->unsignedBigInteger('personal_data_allowed_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->dropColumn(['allow_personal_data', 'personal_data_allowed_at', 'personal_data_allowed_by']);
        });
    }
};
