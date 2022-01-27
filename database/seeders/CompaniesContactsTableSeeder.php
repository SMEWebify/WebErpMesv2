<?php

namespace Database\Seeders;

use App\Models\Companies\CompaniesContacts;
use Illuminate\Database\Seeder;

class CompaniesContactsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CompaniesContacts::factory()->count(200)->create();
    }
}
