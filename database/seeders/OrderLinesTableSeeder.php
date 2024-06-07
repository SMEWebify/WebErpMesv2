<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\OrderLineDetails;

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
        $OrderLines = OrderLines::factory()->count(5000)->create();

        foreach ($OrderLines as $orderLine) {
            OrderLineDetails::factory()
            ->create(['order_lines_id' => $orderLine->id]);
        }
    }
}
