<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Accounting\AccountingPaymentConditions;

class AccountingPaymentConditionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AccountingPaymentConditions::factory()->count(5)->create();
    }
}
