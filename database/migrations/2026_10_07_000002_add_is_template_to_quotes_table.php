<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trame de devis : un devis marqué comme modèle, édité avec l'écran devis
 * habituel et recopié pour démarrer un nouveau devis. Exclu des listes et des
 * indicateurs par le scope global de Quotes.
 *
 * Colonne additive, false par défaut : aucun devis existant ne change de nature.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->boolean('is_template')->default(false)->after('statu');
            $table->index('is_template');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex(['is_template']);
            $table->dropColumn('is_template');
        });
    }
};
