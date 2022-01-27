<?php

namespace Database\Factories\Accounting;

use App\Models\acPaymentConditions;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountingPaymentConditionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = acPaymentConditions::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $type = $this->faker->randomElements(['NODEF', '30FDM15', '30FDM', '30NET', '45FDM']);

        return [
            //
            'code' => $this->$type,
            'label' =>$this->$type,
            'number_of_month' => $this->faker->randomDigitNotNull(),
            'number_of_day' => $this->faker->randomDigitNotNull(),
            'month_end' => $this->faker->randomElement([1, 2]),
        ];
    }
}
