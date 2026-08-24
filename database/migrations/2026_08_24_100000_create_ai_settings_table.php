<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuration de l'assistant IA : provider actif, clé API (chiffrée), modèle.
 *
 * Table singleton (une seule ligne utile) — même approche que factory/settings.
 * On ne migre pas la clé depuis .env ici : la première visite de l'écran
 * d'admin lit ANTHROPIC_API_KEY si la table est vide et propose de la
 * transférer, ce qui garde la migration exempte de manipulation de secret.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->default('claude');
            // Chiffré au niveau applicatif via le cast 'encrypted' du modèle.
            // Colonne text : le cipher gonfle sensiblement la taille (base64).
            $table->text('api_key')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('max_tokens')->default(2048);
            $table->unsignedInteger('timeout_seconds')->default(60);
            // Uniquement pour Ollama et endpoints OpenAI-compatibles auto-hébergés.
            $table->string('base_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
