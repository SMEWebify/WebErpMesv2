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
    private $qty = '';

    public function definition()
    {
        $this->code = $this->faker->unique()->numerify('PART-####');
        $this->qty = $this->faker->biasedNumberBetween($min = 1, $max = 9990);

        return [
            //
            'orders_id' =>  Orders::all()->random()->id,
            'ordre' => $this->faker->numberBetween($min = 1, $max = 10),
            'code' => $this->code,
			'label' => $this->code,
			'qty' => $this->qty,
			'delivered_remaining_qty' => $this->qty,
			'invoiced_remaining_qty' => $this->qty,
			'methods_units_id' => MethodsUnits::all()->random()->id,
			'selling_price' => $this->faker->biasedNumberBetween($min = 1, $max = 10),
			'discount' => $this->faker->numberBetween($min = 0, $max = 3),
			'accounting_vats_id' => AccountingVat ::all()->random()->id,


            /* I dont know get validity_date from same order where id is use
            * we can use this sql requete after seeder for update delivery date line same as order date
            UPDATE 
                order_lines,
                orders
            SET order_lines.delivery_date = orders.validity_date
            WHERE orders.id = order_lines.orders_id
            */

        ];
    }
}
