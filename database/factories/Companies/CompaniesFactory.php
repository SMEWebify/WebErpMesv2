<?php

namespace Database\Factories\Companies;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Companies\Companies;
use App\Models\User;

class CompaniesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Companies::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => $this->faker->regexify('[A-Z]{5}[0-4]{3}'),
			'label' => $this->faker->unique()->name(),
			'website'=>  $this->faker->domainName(),
			'fbsite'=>   $this->faker->domainName(),
			'twittersite'=>   $this->faker->domainName(),
			'lkdsite'=>   $this->faker->domainName(),
			'siren'=> $this->faker->randomElement([1, 2, 3]), 
			'naf_code'=> $this->faker->regexify('[0-4]{4}[A-Z]{1}'),
			'intra_community_vat' => $this->faker->randomElement([5, 10, 20]), 
			'picture'=> $this->faker->imageUrl(640, 480, 'Companies Logo', true),
			'statu_customer' => $this->faker->randomElement([1, 2, 3]),
			'discount'=> $this->faker->randomElement([0, 1, 5, 10]), 
			'user_id' => User::all()->random()->id,
			'account_general_customer' => $this->faker->randomElement([1, 2, 3]), 
			'account_auxiliary_customer' => $this->faker->randomElement([1, 2, 3]),  
			'statu_supplier' => $this->faker->randomElement([1, 2]), 
			'account_general_supplier' => $this->faker->randomElement([1, 2, 3]), 
			'account_auxiliary_supplier'=> $this->faker->randomElement([1, 2, 3]), 
			'recept_controle' => $this->faker->randomElement([0, 1]), 
			'comment'=> $this->faker->realText($maxNbChars = 200, $indexSize = 2),
			'sector_id' => $this->faker->randomElement([1, 2, 3])
            
        ];
    }
}
