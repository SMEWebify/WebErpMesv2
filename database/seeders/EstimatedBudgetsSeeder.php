<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EstimatedBudgetsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('estimated_budgets')->insert([
            'year' => 2024,
            'amount1' => 1500.000,
            'amount2' => 1500.000,
            'amount3' => 1500.000,
            'amount4' => 1500.000,
            'amount5' => 1500.000,
            'amount6' => 1500.000,
            'amount7' => 1500.000,
            'amount8' => 1500.000,
            'amount9' => 1500.000,
            'amount10' => 1500.000,
            'amount11' => 1500.000,
            'amount12' => 1500.000,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
