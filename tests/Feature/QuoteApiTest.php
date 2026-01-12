<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Workflow\Quotes;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuoteApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the show method of QuoteController.
     *
     * @return void
     */
    public function test_can_show_quote()
    {
        // Crée un devis (Quote)
        $quote = Quotes::factory()->create();

        // Fais une requête GET pour afficher ce devis spécifique
        $this->authenticateApiUser();
        $response = $this->getJson("/api/quotes/{$quote->id}");

        // Vérifie que la réponse a le statut 200 (succès)
        $response->assertStatus(200);

        // Vérifie que la réponse contient les données du devis
        $response->assertJson([
            'data' => [
                'id' => $quote->id,
                'code' => $quote->code,
                'label' => $quote->label,
                // Ajoute d'autres attributs selon le modèle
            ],
        ]);
    }
}
