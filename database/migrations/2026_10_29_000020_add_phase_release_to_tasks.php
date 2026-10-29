<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visa de libération des phases (tâches) de l'OF.
 *
 * Purement additive : `methods_services.requires_release` vaut faux par défaut,
 * donc aucun poste ne demande de visa et rien ne change tant qu'on ne coche
 * pas l'option sur un poste de charge. Null sur `tasks` = pas de visa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('methods_services', function (Blueprint $table) {
            if (! Schema::hasColumn('methods_services', 'requires_release')) {
                $table->boolean('requires_release')->default(false);
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'released_at')) {
                $table->timestamp('released_at')->nullable();
            }
            if (! Schema::hasColumn('tasks', 'released_by')) {
                $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'release_comment')) {
                $table->text('release_comment')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['released_by']);
            $table->dropColumn(['released_at', 'released_by', 'release_comment']);
        });

        Schema::table('methods_services', function (Blueprint $table) {
            $table->dropColumn('requires_release');
        });
    }
};
