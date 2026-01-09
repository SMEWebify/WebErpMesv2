<?php

namespace Database\Factories\Workflow;

use App\Models\Workflow\Opportunities;
use App\Models\Workflow\OpportunitiesEventsLogs;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workflow\OpportunitiesEventsLogs>
 */
class OpportunitiesEventsLogsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OpportunitiesEventsLogs::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $opportunity = Opportunities::query()->inRandomOrder()->first()
            ?? Opportunities::factory()->create();

        return [
            'opportunities_id' => $opportunity->id,
            'label' => $this->faker->sentence,
            'type' => $this->faker->numberBetween(1, 4), // Random type between 1 and 4
            'start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'comment' => $this->faker->optional()->text, // Optional comment
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
