<?php

namespace Database\Factories\Methods;

use App\Models\Methods\MethodsUnits;
use Illuminate\Database\Eloquent\Factories\Factory;

class MethodsUnitsFactory extends Factory
{
    protected $model = MethodsUnits::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('UNT???')),
            'label' => $this->faker->words(1, true),
            'type' => $this->faker->numberBetween(1, 5),
            'default' => $this->faker->boolean(),
        ];
    }
}
