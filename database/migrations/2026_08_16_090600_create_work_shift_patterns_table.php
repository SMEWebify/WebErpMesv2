<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Horaires de travail : modèles d'équipes (1×8, 2×8, 3×8, journée, samedi...).
 *
 * Remplace les constantes WORK_START = 8 / WORK_END = 18 de App\Support\WorkingTime,
 * qui imposaient une seule journée continue 8h-18h, du lundi au vendredi, pour tout
 * l'atelier — impossible d'y exprimer une équipe de nuit ni un samedi travaillé.
 *
 * Ce sont des modèles réutilisables, pas des lignes par machine : un atelier a
 * une poignée de régimes horaires qu'on affecte aux ressources.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_shift_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            // Régime appliqué aux ressources qui n'en déclarent pas, et au calcul
            // des dates de tâches. Aucun par défaut = comportement historique.
            $table->boolean('is_default')->default(false);
            $table->string('color')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('work_shift_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_shift_pattern_id')->constrained('work_shift_patterns')->cascadeOnDelete();
            // Jour ISO-8601 : 1 = lundi ... 7 = dimanche.
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            // end_time <= start_time signifie que la plage franchit minuit
            // (poste de nuit 22h00 → 06h00), elle est alors rattachée au jour de départ.
            $table->time('end_time');
            $table->string('label')->nullable();
            $table->timestamps();

            $table->index(['work_shift_pattern_id', 'weekday'], 'work_shift_slots_pattern_weekday_index');
        });

        Schema::table('methods_ressources', function (Blueprint $table) {
            $table->foreignId('work_shift_pattern_id')
                ->nullable()
                ->after('labor_ratio')
                ->constrained('work_shift_patterns')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('methods_ressources', function (Blueprint $table) {
            $table->dropForeign(['work_shift_pattern_id']);
            $table->dropColumn('work_shift_pattern_id');
        });

        Schema::dropIfExists('work_shift_slots');
        Schema::dropIfExists('work_shift_patterns');
    }
};
