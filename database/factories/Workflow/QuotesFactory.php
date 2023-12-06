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

        return [
            //
            'uuid' => $this->faker->uuid(),
            'code' => $this->code,
			'label' => $this->code,
			'customer_reference' => $this->faker->words(7,true) ,
			'companies_id' => Companies::all()->random()->id,
			'companies_contacts_id' => CompaniesContacts::all()->random()->id,
			'companies_addresses_id' => CompaniesAddresses::all()->random()->id,
			'validity_date' => $this->faker->dateTimeInInterval('+1 week', '+41 week'),
			'statu' => $this->faker->numberBetween($min = 1, $max = 6),
			'user_id' => User::all()->random()->id,
			'accounting_payment_conditions_id' => AccountingPaymentConditions::all()->random()->id,
			'accounting_payment_methods_id' => AccountingPaymentMethod::all()->random()->id,
			'accounting_deliveries_id'=> AccountingDelivery::all()->random()->id,
			'comment'=> $this->faker->paragraphs(2, true),
        ];
    }
}
