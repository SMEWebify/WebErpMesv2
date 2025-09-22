<?php

namespace Database\Factories;

use App\Models\Methods\MethodsRessources;
use Illuminate\Database\Eloquent\Factories\Factory;

class MethodsRessourcesFactory extends Factory
{
    protected $model = MethodsRessources::class;

    public function definition(): array
    {
        return [
            'ordre' => $this->faker->numberBetween(1, 100),
            'code' => strtoupper($this->faker->unique()->lexify('RES???')),
            'label' => $this->faker->words(3, true),
            'picture' => null,
            'mask_time' => $this->faker->numberBetween(0, 100),
            'capacity' => $this->faker->randomFloat(3, 1, 100),
            'section_id' => 1,
            'color' => $this->faker->safeHexColor,
            'methods_services_id' => 1,
            'comment' => $this->faker->optional()->sentence(),
        ];
    }
}
