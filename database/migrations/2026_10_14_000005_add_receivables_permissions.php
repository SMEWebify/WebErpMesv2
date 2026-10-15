<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * credit-limit-override : forcer une commande ou un BL au-delà du plafond
 * d'encours, et modifier le plafond d'une société.
 * invoice-reminders-send : envoyer ou tracer une relance de facture.
 * Données au rôle Admin ; les autres rôles les reçoivent à la main.
 */
return new class extends Migration
{
    private array $permissions = ['credit-limit-override', 'invoice-reminders-send'];

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
