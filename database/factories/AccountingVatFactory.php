<?php

namespace Database\Factories;

use App\Models\AccountingVat;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountingVatFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AccountingVat::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $type = $this->faker->randomElements(['TVA0', 'TVA5', 'TVA10', 'TVA20']);

        return [
            //
            'code' => $this->$type,
            'label' =>$this->$type,
            'rate' => $this->faker->randomElement([0, 5, 10, 20]),
        ];
    }
}
