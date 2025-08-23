<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssetRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'asset_manager']);
        Role::firstOrCreate(['name' => 'asset_manager'])->givePermissionTo($permission);
    }
}
