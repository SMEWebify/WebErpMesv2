<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une cote appartient désormais au plan de contrôle de l'article, avec sa place
 * sur le plan (proportions de la page, indépendantes du zoom) et ses données de
 * tolérancement géométrique. Additive : inspection_project_id devient
 * facultatif pour que l'ancien écran d'inspection reste utilisable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_control_points', function (Blueprint $table) {
            $table->unsignedBigInteger('inspection_project_id')->nullable()->change();
        });

        Schema::table('inspection_control_points', function (Blueprint $table) {
            $table->foreignId('inspection_plan_id')->nullable()->after('inspection_project_id')
                ->constrained('inspection_plans')->cascadeOnDelete();
            $table->string('characteristic_type', 20)->default('dimension')->after('category');
            $table->string('gdt_symbol', 30)->nullable()->after('characteristic_type');
            $table->boolean('diameter_zone')->default(false)->after('gdt_symbol');
            $table->string('material_modifier', 1)->nullable()->after('diameter_zone');
            $table->string('datums', 30)->nullable()->after('material_modifier');
            $table->unsignedInteger('count')->default(1)->after('datums');
            $table->string('grid_zone', 10)->nullable()->after('plan_ref');
            $table->boolean('is_fai')->default(true)->after('is_critical');
            $table->unsignedBigInteger('methods_services_id')->nullable()->after('phase');
            $table->decimal('box_x', 8, 6)->nullable();
            $table->decimal('box_y', 8, 6)->nullable();
            $table->decimal('box_w', 8, 6)->nullable();
            $table->decimal('box_h', 8, 6)->nullable();
            $table->decimal('bubble_x', 8, 6)->nullable();
            $table->decimal('bubble_y', 8, 6)->nullable();

            $table->index(['inspection_plan_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::table('inspection_control_points', function (Blueprint $table) {
            $table->dropIndex(['inspection_plan_id', 'number']);
            $table->dropConstrainedForeignId('inspection_plan_id');
            $table->dropColumn([
                'characteristic_type', 'gdt_symbol', 'diameter_zone', 'material_modifier', 'datums',
                'count', 'grid_zone', 'is_fai', 'methods_services_id',
                'box_x', 'box_y', 'box_w', 'box_h', 'bubble_x', 'bubble_y',
            ]);
        });
    }
};
