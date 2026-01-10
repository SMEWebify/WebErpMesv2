<?php

namespace Database\Factories\Methods;

use App\Models\Methods\MethodsStandardNomenclature;
use Illuminate\Database\Eloquent\Factories\Factory;

class MethodsStandardNomenclatureFactory extends Factory
{
    protected $model = MethodsStandardNomenclature::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('STD???')),
            'label' => $this->faker->words(3, true),
            'comment' => $this->faker->optional()->sentence(),
        ];
    }
}
