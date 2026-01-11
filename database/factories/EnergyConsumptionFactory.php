<?php

namespace Database\Factories;

use App\Models\EnergyConsumption;
use App\Models\Methods\MethodsRessources;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnergyConsumptionFactory extends Factory
{
    protected $model = EnergyConsumption::class;

    public function definition(): array
    {
        $kwh = $this->faker->randomFloat(2, 10, 100);
        $cost = $this->faker->randomFloat(2, 0.1, 1);

        return [
            'methods_ressource_id' => MethodsRessources::factory(),
            'kwh' => $kwh,
            'cost_per_kwh' => $cost,
            'total_cost' => round($kwh * $cost, 2),
        ];
    }
}
