<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuration SMTP par instance — table singleton (une seule ligne utile).
 *
 * Même approche que `ai_settings` : la ligne prime sur le .env quand elle
 * existe, sinon on retombe sur MAIL_HOST / MAIL_USERNAME / MAIL_PASSWORD.
 * Le mot de passe est chiffré par le cast `encrypted` du modèle — jamais
 * en clair, jamais loggué.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->string('driver', 32)->default('smtp');
            $table->string('host')->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            // Enum informel : 'tls', 'ssl' ou null. Laravel accepte les trois.
            $table->string('encryption', 8)->nullable();
            $table->string('username')->nullable();
            // Cipher gonfle la taille en base64 → text plutôt que string.
            $table->text('password')->nullable();
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            // Reply-To dynamique : true = utilise l'email de l'utilisateur connecté.
            $table->boolean('reply_to_use_user')->default(true);
            $table->unsignedSmallInteger('timeout')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_settings');
    }
};
