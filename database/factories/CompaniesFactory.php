<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Companies\Companies;

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
            'CODE' => $this->faker->regexify('[A-Z]{5}[0-4]{3}'),
			'LABEL' => $this->faker->unique()->name(),
			'WEBSITE'=>  $this->faker->domainName(),
			'FBSITE'=>   $this->faker->domainName(),
			'TWITTERSITE'=>   $this->faker->domainName(),
			'LKDSITE'=>   $this->faker->domainName(),
			'SIREN'=> $this->faker->siren(),
			'APE'=> $this->faker->regexify('[0-4]{4}[A-Z]{1}'),
			'TVA_INTRA' => $this->faker->vat(),
			'TVA_ID' => $this->faker->randomElement([1, 2, 3]),
			'PICTURE'=> $this->faker->imageUrl(640, 480, 'Companies Logo', true),
			'statu_CLIENT' => $this->faker->randomElement([0, 1]),
			'DISCOUNT'=> $this->faker->randomElement([0, 1, 5, 10]), 
			'users_id' => $this->faker->randomElement([1, 2, 3]), 
			'COMPTE_GEN_CLIENT' => $this->faker->randomElement([1, 2, 3]), 
			'COMPTE_AUX_CLIENT' => $this->faker->randomElement([1, 2, 3]),  
			'statu_FOUR' => $this->faker->randomElement([0, 1]), 
			'COMPTE_GEN_FOUR' => $this->faker->randomElement([1, 2, 3]), 
			'COMPTE_AUX_FOUR'=> $this->faker->randomElement([1, 2, 3]), 
			'RECEPT_CONTROLE' => $this->faker->randomElement([0, 1]), 
			'COMMENT'=> $this->faker->realText($maxNbChars = 200, $indexSize = 2),
			'SECTOR_ID' => $this->faker->randomElement([1, 2, 3])
            
        ];
    }
}
