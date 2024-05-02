<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Accounting\AccountingPaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountingPaymentMethodTest extends TestCase
{
    use RefreshDatabase; // Assure que chaque test est exécuté avec une base de données fraîche

    /**
     * Test the creation of AccountingPaymentMethod using the factory.
     */
    public function testCreateAccountingPaymentMethod()
    {
        // Créer une instance en utilisant la factory
        $paymentMethod = AccountingPaymentMethod::factory()->create();

        // Vérifier que l'instance est bien enregistrée dans la base de données
        $this->assertDatabaseHas('accounting_payment_methods', [
            'code' => $paymentMethod->code,
            'label' => $paymentMethod->label,
            'code_account' => $paymentMethod->code_account,
        ]);

        // Vérifier les attributs pour s'assurer qu'ils correspondent aux données générées par la factory
        $this->assertEquals($paymentMethod->code, $paymentMethod->label);
        $this->assertContains($paymentMethod->code, ['CACHE', 'BANK_CARD', 'BANCK_TRANSFER']);
        $this->assertIsNumeric($paymentMethod->code_account);
    }
}
