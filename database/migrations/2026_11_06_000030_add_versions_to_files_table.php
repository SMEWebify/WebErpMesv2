<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Versions des fichiers de la GED : un plan remplacé reste dans sa lignée
 * (version_group_id = id du premier fichier), marqué remplacé mais jamais
 * supprimé. Tout est nul pour l'existant : un fichier sans lignée est sa propre
 * et unique version.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->unsignedBigInteger('version_group_id')->nullable()->index();
            $table->unsignedInteger('version')->nullable();
            $table->string('revision_label', 20)->nullable();
            $table->timestamp('superseded_at')->nullable()->index();
            $table->unsignedBigInteger('superseded_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['version_group_id']);
            $table->dropIndex(['superseded_at']);
            $table->dropColumn(['version_group_id', 'version', 'revision_label', 'superseded_at', 'superseded_by']);
        });
    }
};
