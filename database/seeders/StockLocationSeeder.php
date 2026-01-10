<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Products\StockLocation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StockLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StockLocation::factory()->count(10)->create();
    }
}
