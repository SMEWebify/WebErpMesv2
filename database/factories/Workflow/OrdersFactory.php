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
    public function definition()
    {
        $code = $this->faker->unique()->numerify('OR-####');

        return [
            //
            'code' => $this->$code,
			'label' => $this->$code,
			'customer_reference' => $this->faker->word,
			'companies_id' => $this->faker->factory(Companies::class),
			'companies_contacts_id' => CompaniesContacts::factory(),
			'companies_addresses_id' => CompaniesAddresses::factory(),
			'validity_date' => $this->faker->dateTimeInInterval('+1 week', '+41 week'),
			'statu' => $this->faker->randomElement([1, 2, 3]),
			'user_id' => User::factory(),
			'accounting_payment_conditions_id' => AccountingPaymentConditions::factory(),
			'accounting_payment_methods_id' => AccountingPaymentMethod::factory(),
			'accounting_deliveries_id'=> AccountingDelivery::factory(),
			'comment'=> $this->faker->paragraphs,
        ];
    }
}
