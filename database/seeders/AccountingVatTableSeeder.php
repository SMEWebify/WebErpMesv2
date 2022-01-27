<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Accounting\AccountingVat;

class AccountingVatTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AccountingVat::factory()->count(4)->create();
    }
}
