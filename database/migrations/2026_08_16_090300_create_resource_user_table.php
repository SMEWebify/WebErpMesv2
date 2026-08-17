<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qui peut tenir une ressource : opérateurs d'un poste manuel, ou personnes
 * habilitées à conduire une machine.
 *
 * `level` porte la profondeur d'habilitation (en formation / autonome / référent)
 * et `certified_until` la date de fin de validité, pour brancher plus tard les
 * habilitations OSH sur la planification.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('methods_ressources_id')->constrained('methods_ressources')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('level')->default(2);
            $table->date('certified_until')->nullable();
            $table->timestamps();

            $table->unique(['methods_ressources_id', 'user_id'], 'resource_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_user');
    }
};
