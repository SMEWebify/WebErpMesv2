<?php

namespace Database\Factories\Products;

use App\Models\Products\Products;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsFamilies;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Products>
 */
class ProductsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => function () {
                do {
                    $code = $this->faker->unique()->lexify('???-PRODUCT');
                } while (Products::where('code', $code)->exists());
                return $code;
            }, 
            'label' => $this->faker->word,
            'ind' => $this->faker->optional()->word,
            'methods_services_id' => MethodsUnits::inRandomOrder()->first()->id,
            'methods_families_id' => MethodsFamilies::inRandomOrder()->first()->id,
            'purchased' => $this->faker->numberBetween(0, 1),
            'purchased_price' => $this->faker->optional()->randomFloat(3, 1, 1000),
            'sold' => $this->faker->numberBetween(0, 1),
            'selling_price' => $this->faker->optional()->randomFloat(3, 1, 1000),
            'methods_units_id' => MethodsUnits::all()->random()->id,
            'material' => $this->faker->optional()->word,
            'thickness' => $this->faker->optional()->randomFloat(3, 0.1, 10),
            'weight' => $this->faker->optional()->randomFloat(3, 0.1, 100),
            'x_size' => $this->faker->optional()->randomFloat(3, 1, 100),
            'y_size' => $this->faker->optional()->randomFloat(3, 1, 100),
            'z_size' => $this->faker->optional()->randomFloat(3, 1, 100),
            'x_oversize' => $this->faker->optional()->randomFloat(3, 0, 10),
            'y_oversize' => $this->faker->optional()->randomFloat(3, 0, 10),
            'z_oversize' => $this->faker->optional()->randomFloat(3, 0, 10),
            'comment' => $this->faker->optional()->text,
            'tracability_type' => $this->faker->numberBetween(1, 3),
            'qty_eco_min' => $this->faker->optional()->randomFloat(3, 1, 100),
            'qty_eco_max' => $this->faker->optional()->randomFloat(3, 1, 100),
            'diameter' => $this->faker->optional()->randomFloat(3, 0.1, 10),
            'diameter_oversize' => $this->faker->optional()->randomFloat(3, 0, 10),
            'section_size' => $this->faker->optional()->randomFloat(3, 1, 100),
            'picture' => $this->faker->optional()->imageUrl(),
            'drawing_file' => $this->faker->optional()->url,
            'stl_file' => $this->faker->optional()->url,
            'svg_file' => $this->faker->optional()->url,
        ];
    }
}
