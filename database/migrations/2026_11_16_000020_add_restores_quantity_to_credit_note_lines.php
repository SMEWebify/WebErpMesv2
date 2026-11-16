<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avoir partiel : une ligne d'avoir remet ou non sa quantité dans le « reste à
 * facturer » de la ligne de commande (retour / refabrication oui, geste commercial
 * ou rebut non refait non). Vrai pour l'existant : tout avoir la remettait.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_note_lines', function (Blueprint $table) {
            $table->boolean('restores_quantity')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('credit_note_lines', function (Blueprint $table) {
            $table->dropColumn('restores_quantity');
        });
    }
};
