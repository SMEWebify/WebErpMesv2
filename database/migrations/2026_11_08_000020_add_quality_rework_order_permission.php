<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * quality-rework-order : lancer un ordre de retouche depuis une NC (tâches sur la
 * ligne de commande) et en reporter la date de livraison. Donnée au rôle Admin ;
 * les autres rôles la reçoivent à la main.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'quality-rework-order', 'guard_name' => 'web']);
        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();

        if ($admin && ! $admin->hasPermissionTo($permission)) {
            $admin->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permission conservée : des rôles ont pu la recevoir à la main.
    }
};
