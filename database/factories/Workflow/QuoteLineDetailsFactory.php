<?php

namespace Database\Factories\Workflow;

use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuoteLineDetails>
 */
class QuoteLineDetailsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    private $x_size = '';
    private $y_size = '';
    private $z_size = '';
    private $x_oversize = '';
    private $y_oversize = '';
    private $z_oversize = '';
    private $thickness = '';
    private $weight = '';

    public function definition()
    {
        $this->x_size = $this->faker->numberBetween($min = 1, $max = 2990);
        $this->y_size = $this->faker->numberBetween($min = 1, $max = 2990);
        $this->z_size = $this->faker->numberBetween($min = 1, $max = 2990);
        $this->x_oversize = $this->faker->numberBetween($min = 1, $max = 10);
        $this->y_oversize = $this->faker->numberBetween($min = 1, $max = 10);
        $this->z_oversize = $this->faker->numberBetween($min = 1, $max = 10);
        $this->thickness = $this->faker->numberBetween($min = 1, $max = 25);
        $this->weight = $this->faker->numberBetween($min = 1, $max = 100);
    

        return [
            'quote_lines_id' => null,
            'x_size' =>$this->x_size,
            'y_size' => $this->y_size, 
            'z_size' => $this->z_size, 
            'x_oversize' => $this->x_oversize,
            'y_oversize' => $this->y_oversize,
            'z_oversize' => $this->z_oversize,
            'thickness' => $this->thickness, 
            'weight' => $this->weight,
        ];
    }
}
