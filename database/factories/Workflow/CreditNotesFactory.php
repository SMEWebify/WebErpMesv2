<?php

namespace Database\Factories\Workflow;

use App\Models\User;
use App\Models\Workflow\CreditNotes;
use App\Models\Workflow\Invoices;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditNotesFactory extends Factory
{
    protected $model = CreditNotes::class;

    public function definition(): array
    {
        $invoice = Invoices::inRandomOrder()->first() ?? Invoices::factory()->create();
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        return [
            'code'                    => $this->faker->unique()->numerify('AV-####'),
            'label'                   => $this->faker->words(3, true),
            'invoices_id'             => $invoice->id,
            'companies_id'            => $invoice->companies_id,
            'companies_contacts_id'   => $invoice->companies_contacts_id,
            'companies_addresses_id'  => $invoice->companies_addresses_id,
            'statu'                   => 1,
            'user_id'                 => $user->id,
        ];
    }
}
