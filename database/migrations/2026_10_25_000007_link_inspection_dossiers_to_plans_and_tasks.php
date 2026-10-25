<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 2 : le dossier d'inspection d'une ligne de commande suit un plan de
 * contrôle validé, et l'opérateur mesure depuis l'écran tâche.
 * - inspection_projects : plan suivi, article, révision, premier article (FAI), clôture ;
 * - inspection_measure_sessions : tâche pendant laquelle on a mesuré ;
 * - inspection_measures : n° de pièce (colonne de la grille) et appareil du module
 *   qualité (quality_control_devices, avec ses étalonnages).
 * Additive : l'ancien écran d'inspection continue de lire ses colonnes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_projects', function (Blueprint $table) {
            $table->foreignId('inspection_plan_id')->nullable()->after('of_id')
                ->constrained('inspection_plans')->nullOnDelete();
            $table->unsignedBigInteger('products_id')->nullable()->after('inspection_plan_id');
            $table->string('revision', 50)->nullable()->after('products_id');
            $table->boolean('is_fai')->default(false)->after('revision');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['order_lines_id', 'inspection_plan_id']);
            $table->index(['products_id', 'revision']);
        });

        Schema::table('inspection_measure_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('tasks_id')->nullable()->after('inspection_project_id');
            $table->index('tasks_id');
        });

        Schema::table('inspection_measures', function (Blueprint $table) {
            $table->unsignedInteger('part_index')->nullable()->after('serial_number');
            $table->foreignId('quality_control_device_id')->nullable()->after('instrument_id')
                ->constrained('quality_control_devices')->nullOnDelete();
            $table->index(['inspection_control_point_id', 'part_index'], 'inspection_measures_point_part');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_measures', function (Blueprint $table) {
            $table->dropIndex('inspection_measures_point_part');
            $table->dropConstrainedForeignId('quality_control_device_id');
            $table->dropColumn('part_index');
        });

        Schema::table('inspection_measure_sessions', function (Blueprint $table) {
            $table->dropIndex(['tasks_id']);
            $table->dropColumn('tasks_id');
        });

        Schema::table('inspection_projects', function (Blueprint $table) {
            $table->dropIndex(['order_lines_id', 'inspection_plan_id']);
            $table->dropIndex(['products_id', 'revision']);
            $table->dropConstrainedForeignId('closed_by');
            $table->dropColumn('closed_at');
            $table->dropColumn(['is_fai', 'revision', 'products_id']);
            $table->dropConstrainedForeignId('inspection_plan_id');
        });
    }
};
