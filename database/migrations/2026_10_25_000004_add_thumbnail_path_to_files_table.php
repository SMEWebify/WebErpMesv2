<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Miniature d'un fichier de la GED, rendue par le navigateur (pdf.js) au premier
 * affichage puis conservée : le serveur n'a besoin d'aucun moteur PDF.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->string('thumbnail_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('thumbnail_path');
        });
    }
};
