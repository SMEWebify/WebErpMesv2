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

        // Un événement (rendez-vous, visite, réunion) tient sur une journée, rarement plus
        $startDate = $this->faker->dateTimeBetween('-6 months', '+1 month');
        $endDate = (clone $startDate)->modify('+' . $this->faker->randomElement([0, 0, 0, 1, 2]) . ' days');

        return [
            'opportunities_id' => $opportunity->id,
            'label' => $this->faker->sentence,
            'type' => $this->faker->numberBetween(1, 4), // Random type between 1 and 4
            'start_date' => $startDate,
            'end_date' => $endDate,
            'comment' => $this->faker->optional()->text, // Optional comment
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
