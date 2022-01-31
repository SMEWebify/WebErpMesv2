<?php

namespace Database\Seeders;

use App\Models\Accounting\AccountingPaymentMethod;
use Illuminate\Database\Seeder;

class AccountingPaymentMethodTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AccountingPaymentMethod::factory()->count(3)->create();
    }
}
