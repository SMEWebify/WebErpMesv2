<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Accounting\AccountingDelivery;

class AccountingDeliveryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AccountingDelivery::factory()->count(3)->create();
    }
}
