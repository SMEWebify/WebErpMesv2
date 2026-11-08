<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Impact d'une NC (coût, délai) et ordre de retouche. Colonnes nullable :
 * les NC existantes restent sans coût ni délai, les tâches existantes sans NC.
 *
 * tasks.origin reçoit une nouvelle valeur, '9' = retouche d'une NC
 * (App\Services\Quality\NonConformityImpact::TASK_ORIGIN).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_non_conformities', function (Blueprint $table) {
            $table->decimal('cost_internal', 12, 2)->nullable()->after('qty');
            $table->decimal('cost_external', 12, 2)->nullable()->after('cost_internal');
            $table->decimal('cost_scrap', 12, 2)->nullable()->after('cost_external');
            $table->integer('delay_days')->nullable()->after('cost_scrap');
            $table->text('impact_comment')->nullable()->after('delay_days');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('quality_non_conformity_id')->nullable()->after('origin')->index();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['quality_non_conformity_id']);
            $table->dropColumn('quality_non_conformity_id');
        });

        Schema::table('quality_non_conformities', function (Blueprint $table) {
            $table->dropColumn(['cost_internal', 'cost_external', 'cost_scrap', 'delay_days', 'impact_comment']);
        });
    }
};
