<?php

namespace Database\Factories\Accounting;

use App\Models\Accounting\AccountingDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountingDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = AccountingDelivery::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */

    private $type = [];

    public function definition()
    {
        $this->type = $this->faker->unique()->randomElement(['FREE_SHIPPING', 'TRANSPORT_COURIER', 'TRANSPORT_CARGO']);

        return [
            //
            'code' => $this->type,
            'label' =>$this->type,
        ];
    }
}
