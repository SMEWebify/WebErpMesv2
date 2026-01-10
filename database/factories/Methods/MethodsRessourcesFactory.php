<?php

namespace Database\Factories\Methods;

use App\Models\Methods\MethodsRessources;
use App\Models\Methods\MethodsSection;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Factories\Factory;

class MethodsRessourcesFactory extends Factory
{
    protected $model = MethodsRessources::class;

    public function definition(): array
    {
        $sectionId = MethodsSection::query()->inRandomOrder()->value('id')
            ?? MethodsSection::factory()->create()->id;
        $serviceId = MethodsServices::query()->inRandomOrder()->value('id')
            ?? MethodsServices::factory()->create()->id;

        return [
            'ordre' => $this->faker->numberBetween(1, 100),
            'code' => strtoupper($this->faker->unique()->lexify('RES???')),
            'label' => $this->faker->words(3, true),
            'picture' => null,
            'mask_time' => $this->faker->numberBetween(0, 100),
            'capacity' => $this->faker->randomFloat(3, 1, 100),
            'section_id' => $sectionId,
            'color' => $this->faker->safeHexColor,
            'methods_services_id' => $serviceId,
            'comment' => $this->faker->optional()->sentence(),
        ];
    }
}
