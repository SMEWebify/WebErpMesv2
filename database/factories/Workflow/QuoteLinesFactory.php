<?php

namespace Database\Factories\Workflow;

use App\Models\Workflow\Quotes;
use App\Models\Workflow\QuoteLines;
use App\Models\Methods\MethodsUnits;
use App\Models\Accounting\AccountingVat;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteLinesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = QuoteLines::class;

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
        $this->qty = $this->faker->numberBetween($min = 1, $max = 2990);

        return [
            //
            'quotes_id' =>  Quotes::all()->random()->id,
            'ordre' => $this->faker->numberBetween($min = 1, $max = 10),
            'code' => $this->code,
			'label' => $this->code,
			'qty' => $this->qty,
			'methods_units_id' => MethodsUnits::all()->random()->id,
			'selling_price' => $this->faker->numberBetween($min = 1, $max = 50),
			'discount' => $this->faker->numberBetween($min = 0, $max = 3),
			'accounting_vats_id' => AccountingVat ::all()->random()->id,


            /* I dont know get validity_date from same order where id is use
            * we can use this sql requete after seeder for update delivery date line same as order date
            UPDATE 
                quote_lines,
                quotes
            SET quote_lines.delivery_date = quotes.validity_date
            WHERE quotes.id = quote_lines.quotes_id
            */

        ];
    }
}
