<?php

namespace Database\Factories\Products;

use App\Models\User;
use App\Models\Products\Stocks;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Products\Products>
 */
class StockLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('LOC###'),  // Generate a unique location code, e.g., 'LOC001'
            'label' => $this->faker->words(3, true),               // Generate a random label with 3 words
            // stocks_id is NOT NULL: reuse a random stock, or create one on an empty database.
            'stocks_id' => Stocks::inRandomOrder()->value('id') ?? Stocks::factory(),
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'end_date' => $this->faker->optional()->dateTimeBetween('-1 year', '+1 year'), // Optional end date between last year and next year
            'comment' => $this->faker->optional()->sentence(),      // Optional comment
            'created_at' => now(),                                  // Creation date (now)
            'updated_at' => now(),                                  // Update date (now)
        ];
    }
}
