<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * dossier-validate : valider (et annuler) le dossier de fin de fabrication d'une
 * ligne de commande, et régler son exigence ligne par ligne, en attendant le
 * connecteur Siteflow. Donnée au rôle Admin ; la qualité la reçoit à la main.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'dossier-validate', 'guard_name' => 'web']);
        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();

        if ($admin && ! $admin->hasPermissionTo($permission)) {
            $admin->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // La permission a pu être donnée à d'autres rôles depuis : on la garde.
    }
};
