<?php

namespace Database\Factories\Companies;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompaniesAddressesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CompaniesAddresses::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $City = $this->faker->city();

        return [
            //
            'companies_id' => Companies::all()->random()->id,
            'ordre' => $this->faker->randomDigitNotNull(),
            'label' =>$City,
            'adress' => $this->faker->secondaryAddress(),
            'zipcode' => $this->faker->postcode(),
            'city' => $City,
            'country' => $this->faker->country(),
            'number' => $this->faker->phoneNumber(),
            'mail' => $this->faker->unique()->safeEmail(),
        ];
    }
}
