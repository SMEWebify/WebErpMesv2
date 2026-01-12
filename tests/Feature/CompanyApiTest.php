<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Companies\Companies;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompanyApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the index method.
     *
     * @return void
     */
    public function test_can_list_companies()
    {
        // Crée quelques compagnies
        Companies::factory()->count(3)->create();

        // Fais une requête GET à l'API
        $this->authenticateApiUser();
        $response = $this->getJson('/api/companies');

        // Vérifie que la réponse a le statut 200
        $response->assertStatus(200);

        // Vérifie que la réponse contient exactement 3 compagnies
        $response->assertJsonCount(3);
    }

    /**
     * Test the show method.
     *
     * @return void
     */
    public function test_can_show_company()
    {
        // Crée une compagnie
        $company = Companies::factory()->create();

        // Fais une requête GET pour afficher cette compagnie spécifique
        $this->authenticateApiUser();
        $response = $this->getJson("/api/companies/{$company->id}");

        // Vérifie que la réponse a le statut 200
        $response->assertStatus(200);

        // Vérifie que la réponse contient les données de la compagnie
        $response->assertJson([
            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                // Ajoute d'autres attributs si nécessaire
            ],
        ]);
    }
}
