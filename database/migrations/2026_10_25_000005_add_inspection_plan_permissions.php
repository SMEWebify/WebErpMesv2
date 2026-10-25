<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * inspection-plans-edit : créer un plan de contrôle et y poser les bulles.
 * inspection-plans-approve : valider un plan de contrôle.
 * Séparées pour que celui qui vérifie puisse ne pas être celui qui a bullé.
 * Données au rôle Admin ; les autres rôles les reçoivent à la main.
 */
return new class extends Migration
{
    private array $permissions = ['inspection-plans-edit', 'inspection-plans-approve'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();

        foreach ($this->permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

            if ($admin && ! $admin->hasPermissionTo($permission)) {
                $admin->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->permissions)->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
