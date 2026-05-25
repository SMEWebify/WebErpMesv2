<?php

namespace Database\Factories\Purchases;

use App\Models\User;
use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Purchases>
 */
class PurchasesFactory extends Factory
{
    protected $model = Purchases::class;

    public function definition()
    {
        $companiesId = Companies::inRandomOrder()->value('id') ?? Companies::factory()->create()->id;

        return [
            'code' => $this->faker->unique()->word,
            'label' => $this->faker->sentence,
            'companies_id' => $companiesId,
            'companies_contacts_id' => CompaniesContacts::inRandomOrder()->value('id') ?? CompaniesContacts::factory()->create(['companies_id' => $companiesId])->id,
            'companies_addresses_id' => CompaniesAddresses::inRandomOrder()->value('id') ?? CompaniesAddresses::factory()->create(['companies_id' => $companiesId])->id,
            'statu' => $this->faker->numberBetween($min = 1, $max = 3),
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory()->create()->id,
            'comment'=> $this->faker->paragraphs(2, true),
        ];
    }
}
