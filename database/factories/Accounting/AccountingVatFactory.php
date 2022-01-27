<?php

namespace Database\Factories\Accounting;

use App\Models\Accounting\AccountingVat;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountingVatFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AccountingVat::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */

    private $type = [];

    public function definition()
    {
        $this->type = $this->faker->unique()->randomElement(['TVA0', 'TVA5', 'TVA10', 'TVA20']);

        return [
            //
            'code' => $this->type,
            'label' =>$this->type,
            'rate' => $this->faker->randomElement([0, 5, 10, 20]),
        ];
    }
}
