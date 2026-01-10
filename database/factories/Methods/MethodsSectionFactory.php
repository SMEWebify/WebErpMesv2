<?php

namespace Database\Factories\Methods;

use App\Models\Methods\MethodsSection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MethodsSectionFactory extends Factory
{
    protected $model = MethodsSection::class;

    public function definition(): array
    {
        $userId = User::query()->inRandomOrder()->value('id') ?? User::factory()->create()->id;

        return [
            'ordre' => $this->faker->numberBetween(1, 20),
            'code' => strtoupper($this->faker->unique()->lexify('SEC???')),
            'label' => $this->faker->words(2, true),
            'user_id' => $userId,
            'color' => $this->faker->safeHexColor,
        ];
    }
}
