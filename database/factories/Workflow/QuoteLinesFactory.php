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
    private $qty = 1;
    private $selling_price = 0;

    public function definition()
    {
        $quote = Quotes::query()->inRandomOrder()->first() ?? Quotes::factory()->create();
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

        // Génération du prix de vente avec proportion inverse à la quantité
        $max_qty = 1000; // Quantité maximale
        $min_qty = 1;    // Quantité minimale

        $min_price = 1;  // Prix minimal
        $max_price = 10; // Prix maximal

        // Calcul du prix de vente avec proportion inverse : plus la quantité est grande, plus le prix est bas
        $this->selling_price = $min_price + (($max_price - $min_price) * (1 - (($this->qty - $min_qty) / ($max_qty - $min_qty))));

        return [
            //
            'quotes_id' =>  $quote->id,
            'ordre' => $this->faker->numberBetween($min = 1, $max = 10),
            'code' => $this->code,
			'label' => $this->code,
			'qty' => $this->qty,
			'methods_units_id' => MethodsUnits::query()->inRandomOrder()->value('id')
                ?? MethodsUnits::factory()->create()->id,
			'selling_price' => $this->selling_price,
			'discount' => $this->faker->numberBetween($min = 0, $max = 3),
			'accounting_vats_id' => AccountingVat::query()->inRandomOrder()->value('id')
                ?? AccountingVat::factory()->create()->id,
            'delivery_date' => $quote->validity_date,
        ];
    }
}
