<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * accounting-manage : régimes et natures de TVA, matrices de TVA, verrouillage des
 * périodes. Les routes l'exigeaient sans qu'aucune migration ni seeder ne la crée :
 * hors de la base de démo, ces écrans restaient masqués et répondaient 403.
 * Donnée au rôle Admin ; les autres rôles la reçoivent à la main.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'accounting-manage', 'guard_name' => 'web']);
        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();

        if ($admin && ! $admin->hasPermissionTo($permission)) {
            $admin->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // La permission existait déjà sur certaines instances (créée à la main) : on la garde.
    }
};
