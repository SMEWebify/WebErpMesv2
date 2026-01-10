<?php

namespace Database\Factories\Admin;

use App\Models\User;
use App\Models\Admin\UserEmploymentContracts;
use App\Models\Methods\MethodsSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserEmploymentContractsFactory extends Factory
{
    protected $model = UserEmploymentContracts::class;

    public function definition(): array
    {
        $userId = User::query()->inRandomOrder()->value('id') ?? User::factory()->create()->id;
        $sectionId = MethodsSection::query()->inRandomOrder()->value('id')
            ?? MethodsSection::factory()->create()->id;

        return [
            'user_id' => $userId,
            'statu' => $this->faker->numberBetween(1, 3),
            'methods_section_id' => $sectionId,
            'signature_date' => $this->faker->date(),
            'type_of_contract' => $this->faker->randomElement(['Permanent', 'Fixed-term']),
            'start_date' => $this->faker->date(),
            'duration_trial_period' => $this->faker->numberBetween(1, 6),
            'end_date' => $this->faker->optional()->date(),
            'weekly_duration' => $this->faker->numberBetween(30, 40),
            'position' => $this->faker->jobTitle(),
            'coefficient' => (string) $this->faker->numberBetween(100, 500),
            'hourly_gross_salary' => $this->faker->randomFloat(2, 10, 50),
            'minimum_monthly_salary' => $this->faker->numberBetween(1500, 3000),
            'annual_gross_salary' => $this->faker->numberBetween(18000, 50000),
            'end_of_contract_reason' => $this->faker->optional()->sentence(),
            'coment' => $this->faker->optional()->sentence(),
        ];
    }
}
