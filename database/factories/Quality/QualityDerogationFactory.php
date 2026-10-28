<?php

namespace Database\Factories\Quality;

use App\Models\User;
use App\Models\Quality\QualityDerogation;
use Illuminate\Database\Eloquent\Factories\Factory;

class QualityDerogationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = QualityDerogation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code'     => 'DER-' . $this->faker->unique()->numerify('#####'),
            'label'    => $this->faker->sentence(3),
            'statu'    => QualityDerogation::STATU_IN_PROGRESS,
            'type'     => QualityDerogation::TYPE_INTERNAL,
            'user_id'  => User::factory(),
            'pb_descp' => $this->faker->sentence(),
            'proposal' => $this->faker->sentence(),
        ];
    }
}
