<?php

namespace Database\Factories\Workflow;

use App\Models\Workflow\Opportunities;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Workflow\OpportunitiesActivitiesLogs;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workflow\OpportunitiesActivitiesLogs>
 */
class OpportunitiesActivitiesLogsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OpportunitiesActivitiesLogs::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $opportunity = Opportunities::query()->inRandomOrder()->first()
            ?? Opportunities::factory()->create();

        return [
            'opportunities_id' => $opportunity->id,
            'label' => $this->faker->sentence(3),
            'type' => $this->faker->numberBetween(1, 5), // Random type between 1 and 5
            'statu' => $this->faker->numberBetween(1, 4), // Random status between 1 and 4
            'priority' => $this->faker->numberBetween(1, 4), // Random priority between 1 and 4
            'due_date' => $this->faker->optional()->date(), // Optional due date
            'comment' => $this->faker->optional()->sentence(), // Optional comment
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
