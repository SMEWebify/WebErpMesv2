<?php

namespace Database\Factories\Methods;

use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Factories\Factory;

class MethodsFamiliesFactory extends Factory
{
    protected $model = MethodsFamilies::class;

    public function definition(): array
    {
        $serviceId = MethodsServices::query()->inRandomOrder()->value('id')
            ?? MethodsServices::factory()->create()->id;

        return [
            'code' => strtoupper($this->faker->unique()->lexify('FAM???')),
            'label' => $this->faker->words(2, true),
            'methods_services_id' => $serviceId,
        ];
    }
}
