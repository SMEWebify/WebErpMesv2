<?php

namespace Database\Seeders;

use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Seeder;

class QuoteLinesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        QuoteLines::factory()->count(5000)->create();
    }
}
