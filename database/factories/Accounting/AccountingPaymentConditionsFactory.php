<?php

namespace Database\Factories\Accounting;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Accounting\AccountingPaymentConditions;

class AccountingPaymentConditionsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AccountingPaymentConditions::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */

    private $type = [];

    public function definition()
    {
        $this->type = $this->faker->unique()->randomElement(['NODEF', '30FDM15', '30FDM', '30NET', '45FDM']);

        return [
            //
            'code' => $this->type,
            'label' =>$this->type,
            'number_of_month' => $this->faker->randomDigitNotNull(),
            'number_of_day' => $this->faker->randomDigitNotNull(),
            'month_end' => $this->faker->randomElement([1, 2]),
        ];
    }
}
