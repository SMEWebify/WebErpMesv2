<?php

namespace Database\Factories;

use App\Models\UserExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserExpenseCategoryFactory extends Factory
{
    protected $model = UserExpenseCategory::class;

    public function definition(): array
    {
        return [
            'label' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
        ];
    }
}
