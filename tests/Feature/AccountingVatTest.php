<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Accounting\AccountingVat;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountingVatTest extends TestCase
{
    use RefreshDatabase; // Assure que chaque test est exécuté avec une base de données fraîche

    /**
     * Test the creation of AccountingVat using the factory.
     */
    public function testCreateAccountingVat()
    {
        // Créer une instance en utilisant la factory
        $vat = AccountingVat::factory()->create();

        // Vérifier que l'instance est bien enregistrée dans la base de données
        $this->assertDatabaseHas('accounting_vats', [
            'code' => $vat->code,
            'label' => $vat->label,
            'rate' => $vat->rate,
        ]);

        // Vérifier les attributs pour s'assurer qu'ils correspondent aux données générées par la factory
        $this->assertEquals($vat->code, $vat->label);
        $this->assertContains($vat->code, ['TVA0', 'TVA5', 'TVA10', 'TVA20']);
        $this->assertContains($vat->rate, [0, 5, 10, 20]);
    }
}
