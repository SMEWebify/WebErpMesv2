<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indices et variantes de devis : chaque indice (B, C…) et chaque variante pointe
 * vers le devis référent (parent_quote_id), qui garde ses trois colonnes à null.
 *
 * Colonnes additives et nullable : un devis existant reste un devis sans indice,
 * imprimé à l'identique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_quote_id')->nullable()->after('is_template');
            $table->string('revision', 10)->nullable()->after('parent_quote_id');
            $table->string('variant_label', 100)->nullable()->after('revision');
            $table->index('parent_quote_id');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex(['parent_quote_id']);
            $table->dropColumn(['parent_quote_id', 'revision', 'variant_label']);
        });
    }
};
