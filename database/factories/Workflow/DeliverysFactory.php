<?php

namespace Database\Factories\Workflow;

use App\Models\User;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\Orders;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeliverysFactory extends Factory
{
    protected $model = Deliverys::class;

    public function definition(): array
    {
        $company = Companies::inRandomOrder()->first()
            ?? Companies::factory()->create(['statu_customer' => 2]);
        $contact = CompaniesContacts::where('companies_id', $company->id)->inRandomOrder()->first()
            ?? CompaniesContacts::factory()->create(['companies_id' => $company->id]);
        $address = CompaniesAddresses::where('companies_id', $company->id)->inRandomOrder()->first()
            ?? CompaniesAddresses::factory()->create(['companies_id' => $company->id]);
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        return [
            'uuid'                    => Str::uuid(),
            'code'                    => $this->faker->unique()->numerify('BL-####'),
            'label'                   => $this->faker->words(3, true),
            'companies_id'            => $company->id,
            'companies_contacts_id'   => $contact->id,
            'companies_addresses_id'  => $address->id,
            'statu'                   => 1,
            'user_id'                 => $user->id,
        ];
    }
}
