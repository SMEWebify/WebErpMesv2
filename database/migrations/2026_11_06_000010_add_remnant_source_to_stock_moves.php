<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chute de tôle remise en stock : l'entrée (typ_move 5) pointe vers la sortie
 * de la pièce dont elle provient. Colonne seule, nullable : les mouvements
 * existants et toutes les sommes de stock sont inchangés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_moves', function (Blueprint $table) {
            $table->foreignId('remnant_source_move_id')
                ->nullable()
                ->after('batch_id')
                ->constrained('stock_moves')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_moves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('remnant_source_move_id');
        });
    }
};
