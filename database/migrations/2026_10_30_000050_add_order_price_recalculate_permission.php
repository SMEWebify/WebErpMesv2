<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * order-price-recalculate : réactiver le prix calculé (relu sur la gamme) d'une
 * ligne de commande sortie de préparation (statut au-delà d'« Ouvert » ou revue
 * de contrat validée). Donnée au rôle Admin ; les autres rôles la reçoivent à la main.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'order-price-recalculate', 'guard_name' => 'web']);
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
