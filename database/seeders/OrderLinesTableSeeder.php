<?php

namespace Database\Seeders;

use App\Models\Workflow\OrderLines;
use Illuminate\Database\Seeder;

class OrderLinesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        OrderLines::factory()->count(5000)->create();
    }
}
