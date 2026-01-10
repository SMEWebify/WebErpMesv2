<?php

namespace Database\Factories\Workflow;

use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderLines;
use App\Models\Methods\MethodsUnits;
use App\Models\Accounting\AccountingVat;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderLinesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OrderLines::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    private $code = '';
    private $qty = 1;
    private $selling_price = 0;

    public function definition()
    {
        $order = Orders::query()->inRandomOrder()->first() ?? Orders::factory()->create();
        $this->code = $this->faker->unique()->numerify('PART-####');
        $this->qty = $this->faker->randomFloat(2, 0, 1); // Generates a random number between 0 and 1

        if ($this->qty <= 0.6) {
            // 60% of cases: quantity between 1 and 100 with bias towards small values
            $this->qty = $this->faker->biasedNumberBetween(1, 100, function ($x) {
                return pow($x, 2); // Bias towards small values ​​in this range
            });
        } else {
            // 40% of cases: quantity between 101 and 1000 with bias towards small values
            $this->qty = $this->faker->biasedNumberBetween(101, 1000, function ($x) {
                return pow($x, 2); // Bias towards small values ​​in this range
            });
        }
        $statu = $order->statu;

        // Generation of the sales price with inverse proportion to the quantity
        $max_qty = 1000; // Qty Minimum
        $min_qty = 1;    // Qty Maximum

        $min_price = 1;  // Minimum price
        $max_price = 10; // Maximum price

        // Calculation of the selling price with inverse proportion: the greater the quantity, the lower the price
        $this->selling_price = $min_price + (($max_price - $min_price) * (1 - (($this->qty - $min_qty) / ($max_qty - $min_qty))));

        return [
            //
            'orders_id' =>  $order->id,
            'ordre' => $this->faker->numberBetween($min = 1, $max = 10),
            'code' => $this->code,
			'label' => $this->code,
			'qty' => $this->qty,
			'delivered_remaining_qty' => function () use ($statu) {
                if ($statu == 3) {
                    return 0; // If the status is 3, we set the remaining delivered quantity to 0
                }
                
                return $this->qty;
            },
            'invoiced_remaining_qty' => function () use ($statu) {
                if ($statu == 3) {
                    return 0; // If the status is 3, we set the remaining Invoiced quantity to 0
                }
                
                return $this->qty;
            },
			'methods_units_id' => MethodsUnits::query()->inRandomOrder()->value('id')
                ?? MethodsUnits::factory()->create()->id,
			'selling_price' => $this->selling_price,
			'discount' => $this->faker->numberBetween($min = 0, $max = 3),
			'accounting_vats_id' => AccountingVat::query()->inRandomOrder()->value('id')
                ?? AccountingVat::factory()->create()->id,
            
            'delivery_status' => $statu,
            'internal_delay' => $order->validity_date,
            'delivery_date' => $order->validity_date,
        ];
    }
}
