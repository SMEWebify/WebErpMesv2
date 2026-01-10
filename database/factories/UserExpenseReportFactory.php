<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserExpenseReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserExpenseReportFactory extends Factory
{
    protected $model = UserExpenseReport::class;

    public function definition(): array
    {
        $userId = User::query()->inRandomOrder()->value('id') ?? User::factory()->create()->id;

        return [
            'user_id' => $userId,
            'date' => $this->faker->date(),
            'label' => $this->faker->words(3, true),
            'status' => $this->faker->numberBetween(1, 3),
        ];
    }
}
