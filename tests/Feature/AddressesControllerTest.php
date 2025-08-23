<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddressesControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the store method of AddressesController.
     *
     * @return void
     */
    public function test_can_store_an_address()
    {
        // Simulation d'une requête pour créer une nouvelle adresse
        $data = [
            'street' => '123 Rue des Lilas', // Exemple d'un champ de CompaniesAddresses
            'city' => 'Paris',
            'zip_code' => '75000',
            'companies_id' => 1, // ID de la compagnie associée
            'default' => 1,
        ];

        // Fais une requête POST pour créer l'adresse
        $response = $this->post(route('addresses.store'), $data);

        // Vérifie que l'adresse est bien créée
        $this->assertDatabaseHas('companies_addresses', [
            'street' => '123 Rue des Lilas',
            'city' => 'Paris',
        ]);

        // Vérifie que la redirection s'est bien faite vers la bonne route
        $response->assertRedirect(route('companies.show', ['id' => 1]))
                ->assertSessionHas('success', 'Successfully created adress');
    }

    /**
     * Test the update method of AddressesController.
     *
     * @return void
     */
    public function test_can_update_an_address()
    {
        // Crée une adresse existante pour la mise à jour
        $address = CompaniesAddresses::factory()->create([
            'street' => '123 Rue des Fleurs',
            'city' => 'Lyon',
            'zip_code' => '69000',
            'companies_id' => 1,
        ]);

        // Simulation des données de la requête de mise à jour
        $data = [
            'id' => $address->id,
            'street' => '456 Rue de la Paix', // Nouveau champ modifié
            'city' => 'Marseille',
            'zip_code' => '13000',
            'companies_id' => 1,
            'defaultAdress_update' => true,
        ];

        // Fais une requête POST pour mettre à jour l'adresse
        $response = $this->put(route('addresses.update', ['id' => $address->id]), $data);

        // Vérifie que la base de données contient les données mises à jour
        $this->assertDatabaseHas('companies_addresses', [
            'id' => $address->id,
            'street' => '456 Rue de la Paix',
            'city' => 'Marseille',
        ]);

        // Vérifie que la redirection s'est bien faite vers la bonne route
        $response->assertRedirect(route('companies.show', ['id' => 1]))
                ->assertSessionHas('success', 'Successfully updated adress');
    }

}
