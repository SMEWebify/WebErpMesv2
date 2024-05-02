<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Accounting\AccountingPaymentConditions;

class AccountingPaymentConditionsTest extends TestCase
{
    use RefreshDatabase; // Assure que chaque test est exécuté avec une base de données fraîche

    /**
     * Test the creation of AccountingPaymentConditions using the factory.
     */
    public function testCreateAccountingPaymentConditions()
    {
        // Créer une instance en utilisant la factory
        $paymentCondition = AccountingPaymentConditions::factory()->create();

        // Vérifier que l'instance est bien enregistrée dans la base de données
        $this->assertDatabaseHas('accounting_payment_conditions', [
            'code' => $paymentCondition->code,
            'label' => $paymentCondition->label,
            'number_of_month' => $paymentCondition->number_of_month,
            'number_of_day' => $paymentCondition->number_of_day,
            'month_end' => $paymentCondition->month_end
        ]);

        // Vérifier les attributs pour s'assurer qu'ils correspondent aux données générées par la factory
        $this->assertEquals($paymentCondition->code, $paymentCondition->label);
        $this->assertContains($paymentCondition->code, ['NODEF', '30FDM15', '30FDM', '30NET', '45FDM']);
        $this->assertContains($paymentCondition->month_end, [1, 2]);
        $this->assertIsNumeric($paymentCondition->number_of_month);
        $this->assertIsNumeric($paymentCondition->number_of_day);
    }
}
