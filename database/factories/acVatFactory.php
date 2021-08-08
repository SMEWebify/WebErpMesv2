<?php

namespace Database\Factories;

use App\Models\acVatCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

class acVatFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = acVatCondition::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $type = $this->faker->randomElements(['TVA0', 'TVA5', 'TVA10', 'TVA20']);

        return [
            //
            'CODE' => $this->$type,
            'LABEL' =>$this->$type,
            'RATE' => $this->faker->randomElement([0, 5, 10, 20]),
        ];
    }
}
