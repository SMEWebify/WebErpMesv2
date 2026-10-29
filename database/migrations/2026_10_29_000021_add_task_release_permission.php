<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * task-release : viser (et annuler le visa de) une phase terminée sur un poste
 * de charge qui demande la libération. Donnée au rôle Admin ; les autres rôles
 * la reçoivent à la main.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'task-release', 'guard_name' => 'web']);
        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();

        if ($admin && ! $admin->hasPermissionTo($permission)) {
            $admin->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permission conservée : elle a pu être donnée à d'autres rôles entre-temps.
    }
};
