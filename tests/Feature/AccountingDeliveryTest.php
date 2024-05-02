<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Accounting\AccountingDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountingDeliveryTest extends TestCase
{
    use RefreshDatabase; // Cela s'assure que la base de données est réinitialisée pour chaque test

    public function testCreateAccountingDeliveryWithFactoryDefaults()
    {
        // Créer une instance de AccountingDelivery en utilisant la factory sans arguments supplémentaires
        $delivery = AccountingDelivery::factory()->create();

        // Vérifier que l'instance a été sauvegardée dans la base de données avec les attributs générés par la factory
        $this->assertDatabaseHas('accounting_deliveries', [
            'code' => $delivery->code,
            'label' => $delivery->label,
        ]);

        // Vérifier que les attributs correspondent à ceux générés par la factory
        $this->assertContains($delivery->code, ['FREE_SHIPPING', 'TRANSPORT_COURIER', 'TRANSPORT_CARGO']);
        $this->assertEquals($delivery->code, $delivery->label);
    }
}
