<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        for ($i=1; $i < 50; $i++) {
            $user = User::create([
                'name' => 'User '.$i,
                'email' => 'test'.$i.'@test.com',
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);
            $role = Role::where('id', 5)->first();
            $permission = Permission::where('name', 'N/A')->first();
            $user->syncRoles($role)->syncPermissions($permission);
        }
    }
}