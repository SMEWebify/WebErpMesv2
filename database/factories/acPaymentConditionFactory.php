<?php

namespace Database\Factories;

use App\Models\acPaymentConditions;
use Illuminate\Database\Eloquent\Factories\Factory;

class acPaymentConditionFactory extends Factory
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
            'CODE' => $this->$type,
            'LABEL' =>$this->$type,
            'NUMBER_OF_MONTH' => $this->faker->randomDigitNotNull(),
            'NUMBER_OF_DAY' => $this->faker->randomDigitNotNull(),
            'MONTH_END' => $this->faker->randomElement([1, 2]),
        ];
    }
}
