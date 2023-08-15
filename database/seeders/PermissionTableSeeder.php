<?php

namespace Database\Seeders;


use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionTableSeeder  extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
                        'companies-menu',
                        'leads-menu',
                        'quotes-menu',
                        'orders-menu',
                        'scheduling-menu',
                        'deliverys-menu',
                        'invoices-menu',
                        'products-menu',
                        'purchases-menu',
                        'quality-menu',
                        'settings-time-menu',
                        'methods-menu',
                        'accouting-menu',
                        'human-resources-menu',
                        'your-company-menu',
                        ];
    
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }
}