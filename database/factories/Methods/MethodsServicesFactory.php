<?php

namespace Database\Factories\Methods;

use App\Models\Methods\MethodsServices;
use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Factories\Factory;

class MethodsServicesFactory extends Factory
{
    protected $model = MethodsServices::class;

    public function definition(): array
    {
        $companyId = Companies::query()->inRandomOrder()->value('id')
            ?? Companies::factory()->create()->id;

        return [
            'code' => strtoupper($this->faker->unique()->lexify('SRV???')),
            'ordre' => $this->faker->numberBetween(1, 10),
            'label' => $this->faker->words(2, true),
            'type' => $this->faker->numberBetween(1, 8),
            'hourly_rate' => $this->faker->randomFloat(2, 10, 200),
            'margin' => $this->faker->randomFloat(2, 0, 50),
            'color' => $this->faker->safeHexColor,
            'picture' => null,
            'companies_id' => $companyId,
        ];
    }
}
