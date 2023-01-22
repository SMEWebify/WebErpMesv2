<?php

namespace Database\Seeders;

use App\Models\Workflow\QuoteLineDetails;
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
        $QuoteLines = QuoteLines::factory()->count(500)->create();

        foreach ($QuoteLines as $key => $QuoteLine) {
            QuoteLineDetails::factory()
                ->for($QuoteLine, 'QuoteLines')
                ->create();
        }
    }
}
