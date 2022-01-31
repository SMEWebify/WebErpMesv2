<?php

namespace Database\Factories\Accounting;

use App\Models\Accounting\AccountingPaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountingPaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = AccountingPaymentMethod::class;

    private $type = [];

    public function definition()
    {
        $this->type = $this->faker->unique()->randomElement(['CACHE', 'BANK_CARD', 'BANCK_TRANSFER']);

        return [
            //
            'code' => $this->type,
            'label' =>$this->type,
            'code_account' => $this->faker->randomDigitNotNull(),
        ];
    }
}
