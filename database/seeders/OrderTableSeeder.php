<?php

namespace Database\Seeders;

use App\Models\Workflow\Orders;
use Illuminate\Database\Seeder;

class OrderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Orders::factory()->count(500)->create();
    }
}
