<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Masquer les temps et l'avancement de l'écran tâche aux opérateurs
 * (App\Services\Production\ProductionTimesVisibility). Faux par défaut : écran
 * inchangé. production-times-view (Admin) garde l'affichage complet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factory', function (Blueprint $table) {
            $table->boolean('hide_production_times')->default(false)->after('scan_mode');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'production-times-view', 'guard_name' => 'web']);
        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();

        if ($admin && ! $admin->hasPermissionTo($permission)) {
            $admin->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('factory', function (Blueprint $table) {
            $table->dropColumn('hide_production_times');
        });
        // Permission conservée : elle a pu être donnée à d'autres rôles entre-temps.
    }
};
