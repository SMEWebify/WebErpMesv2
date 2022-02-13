<?php

namespace Database\Seeders;

use App\Models\Workflow\Quotes;
use Illuminate\Database\Seeder;

class QuotesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Quotes::factory()->count(500)->create();
    }
}
