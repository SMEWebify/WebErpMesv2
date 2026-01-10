<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\Companies;
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
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $company = Companies::factory()->create(['user_id' => $user->id]);

        // Simulation d'une requête pour créer une nouvelle adresse
        $data = [
            'label' => 'Bureau principal',
            'adress' => '123 Rue des Lilas',
            'city' => 'Paris',
            'zipcode' => '75000',
            'companies_id' => $company->id,
            'country' => 'France',
            'ordre' => 1,
            'default' => 1,
        ];

        // Fais une requête POST pour créer l'adresse
        $response = $this->post(route('addresses.store', ['id' => $company->id]), $data);

        // Vérifie que l'adresse est bien créée
        $this->assertDatabaseHas('companies_addresses', [
            'adress' => '123 Rue des Lilas',
            'city' => 'Paris',
        ]);

        // Vérifie que la redirection s'est bien faite vers la bonne route
        $response->assertRedirect(route('companies.show', ['id' => $company->id]))
                ->assertSessionHas('success', 'Successfully created adress');
    }

    /**
     * Test the update method of AddressesController.
     *
     * @return void
     */
    public function test_can_update_an_address()
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $company = Companies::factory()->create(['user_id' => $user->id]);

        // Crée une adresse existante pour la mise à jour
        $address = CompaniesAddresses::factory()->create([
            'label' => 'Adresse secondaire',
            'adress' => '123 Rue des Fleurs',
            'city' => 'Lyon',
            'zipcode' => '69000',
            'companies_id' => $company->id,
        ]);

        // Simulation des données de la requête de mise à jour
        $data = [
            'id' => $address->id,
            'label' => 'Adresse principale',
            'adress' => '456 Rue de la Paix', // Nouveau champ modifié
            'city' => 'Marseille',
            'zipcode' => '13000',
            'companies_id' => $company->id,
            'country' => 'France',
            'ordre' => 1,
            'defaultAdress_update' => true,
        ];

        // Fais une requête POST pour mettre à jour l'adresse
        $response = $this->post(route('addresses.update', ['id' => $address->id]), $data);

        // Vérifie que la base de données contient les données mises à jour
        $this->assertDatabaseHas('companies_addresses', [
            'id' => $address->id,
            'adress' => '456 Rue de la Paix',
            'city' => 'Marseille',
        ]);

        // Vérifie que la redirection s'est bien faite vers la bonne route
        $response->assertRedirect(route('companies.show', ['id' => $company->id]))
                ->assertSessionHas('success', 'Successfully updated adress');
    }

}
