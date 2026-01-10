<?php

namespace Database\Factories\Workflow;

use App\Models\User;
use App\Models\Workflow\Quotes;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Accounting\AccountingPaymentConditions;

class QuotesFactory extends Factory
{
    

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Quotes::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    private $code;

    
    public function definition()
    {
        $this->code = $this->faker->unique()->numerify('QT-####');
        $companyId = Companies::query()->inRandomOrder()->value('id')
            ?? Companies::factory()->create()->id;
        $contactId = CompaniesContacts::query()->inRandomOrder()->value('id')
            ?? CompaniesContacts::factory()->create(['companies_id' => $companyId])->id;
        $addressId = CompaniesAddresses::query()->inRandomOrder()->value('id')
            ?? CompaniesAddresses::factory()->create(['companies_id' => $companyId])->id;
        $userId = User::query()->inRandomOrder()->value('id') ?? User::factory()->create()->id;
        $paymentConditionId = AccountingPaymentConditions::query()->inRandomOrder()->value('id')
            ?? AccountingPaymentConditions::factory()->create()->id;
        $paymentMethodId = AccountingPaymentMethod::query()->inRandomOrder()->value('id')
            ?? AccountingPaymentMethod::factory()->create()->id;
        $deliveryId = AccountingDelivery::query()->inRandomOrder()->value('id')
            ?? AccountingDelivery::factory()->create()->id;

        return [
            //
            'uuid' => $this->faker->uuid(),
            'code' => $this->code,
			'label' => $this->code,
			'customer_reference' => $this->faker->words(7,true) ,
			'companies_id' => $companyId,
			'companies_contacts_id' => $contactId,
			'companies_addresses_id' => $addressId,
			'validity_date' => $this->faker->dateTimeInInterval('+1 week', '+41 week'),
			'statu' => $this->faker->numberBetween($min = 1, $max = 6),
			'user_id' => $userId,
			'accounting_payment_conditions_id' => $paymentConditionId,
			'accounting_payment_methods_id' => $paymentMethodId,
			'accounting_deliveries_id'=> $deliveryId,
			'comment'=> $this->faker->paragraphs(2, true),
        ];
    }
}
