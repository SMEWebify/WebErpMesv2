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
        $CODE = $this->faker->unique()->numerify('OR-####');

        return [
            //
            'CODE' => $this->$CODE,
			'LABEL' => $this->$CODE,
			'customer_reference' => $this->faker->word,
			'companies_id' => $this->faker->factory(Companies::class),
			'companies_contacts_id' => $this->faker->factory(CompaniesContacts::class),
			'companies_addresses_id' => $this->faker->factory(CompaniesAddresses::class),
			'validity_date' => $this->faker->dateTimeInInterval('+1 week', '+41 week'),
			'statu' => $this->faker->randomElement([1, 2, 3]),
			'user_id' => $this->faker->factory(User::class),
			'accounting_payment_conditions_id' => $this->faker->factory(AccountingPaymentConditions::class),
			'accounting_payment_methods_id' => $this->faker->factory(AccountingPaymentMethod::class),
			'accounting_deliveries_id'=> $this->faker->factory(AccountingDelivery::class),
			'comment'=> $this->faker->paragraphs,
        ];
    }
}
