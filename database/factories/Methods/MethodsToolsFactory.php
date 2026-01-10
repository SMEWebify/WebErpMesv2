<?php

namespace Database\Factories\Methods;

use App\Models\Methods\MethodsTools;
use Illuminate\Database\Eloquent\Factories\Factory;

class MethodsToolsFactory extends Factory
{
    protected $model = MethodsTools::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('TOOL???')),
            'label' => $this->faker->words(2, true),
            'ETAT' => 1,
            'cost' => $this->faker->randomFloat(2, 10, 500),
            'picture' => null,
            'end_date' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'comment' => $this->faker->optional()->sentence(),
            'qty' => $this->faker->numberBetween(1, 20),
            'availability' => true,
        ];
    }
}
