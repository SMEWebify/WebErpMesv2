<?php

namespace Database\Factories\Products;

use App\Models\User;
use App\Models\Products\Products;
use App\Models\Products\StockLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Products\Products>
 */
class StockLocationProductsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('STKLOC###'),  // Generate a unique code, e.g., 'STKLOC001'
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            // Reuse random parents, or create them on an empty database.
            'stock_locations_id' => StockLocation::inRandomOrder()->value('id') ?? StockLocation::factory(),
            'products_id' => Products::inRandomOrder()->value('id') ?? Products::factory(),
            'mini_qty' => $this->faker->numberBetween(1, 100),       // Generate a random minimum quantity between 1 and 100
            'end_date' => $this->faker->optional()->dateTimeBetween('-1 year', '+1 year'), // Optional end date between last year and next year
            'addressing' => $this->faker->sentence(3),               // Random sentence for addressing (3 words)
            'created_at' => now(),                                   // Creation date (now)
            'updated_at' => now(),                                   // Update date (now)
        ];
    }
}
