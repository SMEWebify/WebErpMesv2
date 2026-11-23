<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Type d'équipement (App\Enums\EquipmentKind) du poste de charge et de la
 * ressource. Nullable : l'existant reste « non précisé ».
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['methods_services', 'methods_ressources'] as $table) {
            if (! Schema::hasColumn($table, 'equipment_kind')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->string('equipment_kind', 32)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['methods_services', 'methods_ressources'] as $table) {
            if (Schema::hasColumn($table, 'equipment_kind')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('equipment_kind'));
            }
        }
    }
};
