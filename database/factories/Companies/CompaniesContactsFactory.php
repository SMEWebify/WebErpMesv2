<?php

namespace Database\Factories\Companies;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompaniesContactsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CompaniesContacts::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            //
            'companies_id' => Companies::all()->random()->id,
            'ordre' => $this->faker->randomDigitNotNull(),
            'civility' =>$this->faker->randomElement(['Mr', 'Mrs ', 'Miss ', 'Ms']),
            'first_name' => $this->faker->firstName(),
            'name' => $this->faker->name(),
            'function' => $this->faker->randomElement(['Director', 'Buyer ', 'Technician ', 'Storekeeper']),
            'number' => $this->faker->phoneNumber(),
            'mobile' => $this->faker->safeEmail(),
            'mail' => $this->faker->safeEmail(),
        ];
    }
}
