<?php

namespace Database\Seeders;

use App\Models\Companies\Companies;
use Illuminate\Database\Seeder;

class CompaniesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Companies::factory()->count(50)->create();
    }
}
