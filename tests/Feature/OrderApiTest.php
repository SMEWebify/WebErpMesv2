<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Workflow\Orders;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the show method of OrderController.
     *
     * @return void
     */
    public function test_can_show_order()
    {
        // Crée une commande (Order)
        $order = Orders::factory()->create();

        // Fais une requête GET pour afficher cette commande spécifique
        $this->authenticateApiUser();
        $response = $this->getJson("/api/orders/{$order->id}");

        // Vérifie que la réponse a le statut 200 (succès)
        $response->assertStatus(200);

        // Vérifie que la réponse contient les données de la commande
        $response->assertJson([
            'data' => [
                'id' => $order->id,
                'label' => $order->label,
                // Ajoute d'autres attributs si nécessaire
            ],
        ]);
    }
}
