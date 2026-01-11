<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Purchases\Purchases;
use App\Models\Companies\Companies;
use App\Models\Companies\SupplierRating;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SupplierRatingControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the store method of SupplierRatingController.
     *
     * @return void
     */
    public function test_can_store_supplier_rating()
    {
        $purchase = Purchases::factory()->create();
        $company = Companies::factory()->create();

        // Simulation des données de la requête pour créer une évaluation de fournisseur
        $data = [
            'purchases_id' => $purchase->id, // ID de l'achat associé
            'companies_id' => $company->id, // ID de la compagnie associée
            'rating' => 4,       // Note donnée au fournisseur
            'comment' => 'Great supplier, timely delivery.', // Commentaire optionnel
        ];

        // Fais une requête POST pour créer l'évaluation
        $response = $this->post(route('supplier-ratings.store'), $data);

        // Vérifie que l'évaluation du fournisseur est bien créée dans la base de données
        $this->assertDatabaseHas('supplier_ratings', [
            'purchases_id' => $purchase->id,
            'companies_id' => $company->id,
            'rating' => 4,
            'comment' => 'Great supplier, timely delivery.',
        ]);

        // Vérifie que la redirection s'est bien faite vers la page précédente
        $response->assertRedirect()->with('success', 'Rate saved successfully');
    }

    /**
     * Test that store validation fails for missing required fields.
     *
     * @return void
     */
    public function test_store_fails_without_required_fields()
    {
        // Simulation des données manquantes pour tester la validation
        $data = [
            'rating' => 4,  // Note donnée sans les IDs d'achat et de compagnie
        ];

        // Fais une requête POST avec les données manquantes
        $response = $this->post(route('supplier-ratings.store'), $data);

        // Vérifie que la validation échoue et que la redirection retourne les erreurs
        $response->assertSessionHasErrors(['purchases_id', 'companies_id']);
    }
}
