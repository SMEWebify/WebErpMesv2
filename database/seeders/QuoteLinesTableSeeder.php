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
        $QuoteLines = QuoteLines::factory()->count(5000)->create();

        foreach ($QuoteLines as $quoteLine) {
            QuoteLineDetails::factory()
                ->create(['quote_lines_id' => $quoteLine->id]);
        }
    }
}
