<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Companies\CompaniesAddresses;

class CompaniesAddressesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CompaniesAddresses::factory()->count(200)->create();
    }
}
