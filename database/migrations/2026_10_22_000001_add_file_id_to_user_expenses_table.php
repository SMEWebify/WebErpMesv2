<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Justificatif (fichier de la GED) dont une dépense a été saisie. Additive :
 * l'ancienne colonne scan_file reste lue pour les dépenses existantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_expenses', function (Blueprint $table) {
            $table->foreignId('file_id')->nullable()->after('scan_file')
                ->constrained('files')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('file_id');
        });
    }
};
