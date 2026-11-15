<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ligne de devis créée depuis la dictée (QuoteDictation) : proposée par l'IA,
 * relue et validée par l'utilisateur. Nul pour toutes les lignes existantes.
 * Information interne, jamais imprimée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_lines', function (Blueprint $table) {
            $table->timestamp('ai_proposed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quote_lines', function (Blueprint $table) {
            $table->dropColumn('ai_proposed_at');
        });
    }
};
