<?php

namespace Database\Factories\Companies;

use App\Models\Companies\Companies;
use App\Models\Companies\SupplierRating;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierRatingFactory extends Factory
{
    protected $model = SupplierRating::class;

    public function definition()
    {
        return [
            'purchases_id' => 1, // Exemple d'ID d'achat
            'companies_id' => Companies::all()->random()->id, // Exemple d'ID de compagnie
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->sentence,
            'approved_at' => $this->faker->optional()->dateTimeBetween('-6 months', 'now'),
            'next_review_at' => $this->faker->optional()->dateTimeBetween('now', '+6 months'),
            'evaluation_status' => $this->faker->randomElement(['pending', 'approved', 'under_review', 'rejected']),
            'evaluation_score_quality' => $this->faker->numberBetween(0, 100),
            'evaluation_score_logistics' => $this->faker->numberBetween(0, 100),
            'evaluation_score_service' => $this->faker->numberBetween(0, 100),
            'action_plan' => $this->faker->optional()->sentence,
        ];
    }
}
