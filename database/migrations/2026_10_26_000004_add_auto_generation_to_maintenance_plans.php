<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Génération automatique des ordres de travail préventifs.
 *
 * Additive : un plan existant garde auto_generate = false et ne crée rien tant
 * qu'on ne l'a pas activé (sinon chaque plan « date fixe » passée générerait un
 * ordre au premier passage de la tâche planifiée).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_plans', function (Blueprint $table) {
            $table->boolean('auto_generate')->default(false)->after('fixed_date');
            $table->unsignedInteger('interval_days')->nullable()->after('auto_generate');
            $table->unsignedSmallInteger('lead_days')->default(7)->after('interval_days');
        });

        Schema::table('maintenance_work_orders', function (Blueprint $table) {
            $table->foreignId('maintenance_plan_id')->nullable()->after('asset_id')
                ->constrained('maintenance_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_work_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('maintenance_plan_id');
        });

        Schema::table('maintenance_plans', function (Blueprint $table) {
            $table->dropColumn(['auto_generate', 'interval_days', 'lead_days']);
        });
    }
};
