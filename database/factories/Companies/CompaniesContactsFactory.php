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
            'companies_id' => $this->faker->factory(Companies::class),
            'ORDRE' => $this->faker->randomDigitNotNull(),
            'CIVILITY' =>$this->faker->randomElement(['Mr', 'Mrs ', 'Miss ', 'Ms']),
            'FIRST_NAME' => $this->faker->firstName(),
            'NAME' => $this->faker->name(),
            'FUNCTION' => $this->faker->randomElement(['Director', 'Buyer ', 'Technician ', 'Storekeeper']),
            'NUMBER' => $this->faker->phoneNumber(),
            'MOBILE' => $this->faker->safeEmail(),
            'MAIL' => $this->faker->safeEmail(),
        ];
    }
}
