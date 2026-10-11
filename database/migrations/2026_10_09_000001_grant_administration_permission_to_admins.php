<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The admin/* routes now require `your-company-menu` (they only required a
 * login). That permission already gated the Administration menu, so whoever
 * saw the menu keeps access. This makes sure an instance cannot lock itself
 * out: the Admin role gets it, and if no role holds it at all, the roles of
 * the first account (the one created at install) do. Additive only.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'your-company-menu', 'guard_name' => 'web']);

        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();
        if ($admin && ! $admin->hasPermissionTo($permission)) {
            $admin->givePermissionTo($permission);
        }

        if ($permission->roles()->doesntExist()) {
            $firstUser = User::withTrashed()->orderBy('id')->first();
            foreach ($firstUser?->roles ?? [] as $role) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Nothing to undo: revoking the permission could lock administrators out.
    }
};
