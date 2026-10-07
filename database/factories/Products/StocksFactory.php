<?php

namespace Database\Factories\Products;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Products\Products>
 */
class StocksFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('STOCK###'), // Generates a unique code of the type 'STOCK001'
            'label' => $this->faker->words(2, true),               // Génère un label aléatoire composé de 3 mots
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'created_at' => now(),                                  // Creation date (now)
            'updated_at' => now(),                                  // Update date (now)
        ];
    }
}
