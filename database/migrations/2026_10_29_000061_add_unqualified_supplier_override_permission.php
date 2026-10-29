<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * unqualified-supplier-override : passer une commande d'achat à un fournisseur non
 * qualifié, avec un motif tracé (option purchases.require_qualified_supplier).
 * Donnée au rôle Admin ; les autres rôles la reçoivent à la main.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'unqualified-supplier-override', 'guard_name' => 'web']);
        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();

        if ($admin && ! $admin->hasPermissionTo($permission)) {
            $admin->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permission gardée : des rôles ont pu la recevoir à la main.
    }
};
