<?php

namespace Database\Factories\Workflow;

use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Workflow\Orders;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrdersFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Orders::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    private $code = '';

    public function definition()
    {
        $this->code = $this->faker->unique()->numerify('OR-####');

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
			'statu' => $this->faker->numberBetween($min = 1, $max = 3),
			'user_id' => User::all()->random()->id,
			'accounting_payment_conditions_id' => AccountingPaymentConditions::all()->random()->id,
			'accounting_payment_methods_id' => AccountingPaymentMethod::all()->random()->id,
			'accounting_deliveries_id'=> AccountingDelivery::all()->random()->id,
			'comment'=> $this->faker->paragraphs(2, true),
        ];
    }
}
