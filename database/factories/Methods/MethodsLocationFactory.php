<?php

namespace Database\Factories\Methods;

use App\Models\Methods\MethodsLocation;
use App\Models\Methods\MethodsRessources;
use Illuminate\Database\Eloquent\Factories\Factory;

class MethodsLocationFactory extends Factory
{
    protected $model = MethodsLocation::class;

    public function definition(): array
    {
        $ressourceId = MethodsRessources::query()->inRandomOrder()->value('id')
            ?? MethodsRessources::factory()->create()->id;

        return [
            'code' => strtoupper($this->faker->unique()->lexify('LOC???')),
            'label' => $this->faker->words(2, true),
            'ressource_id' => $ressourceId,
            'color' => $this->faker->safeHexColor,
        ];
    }
}
