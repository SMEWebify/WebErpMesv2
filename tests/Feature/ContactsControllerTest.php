<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContactsControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the store method of ContactsController.
     *
     * @return void
     */
    public function test_can_store_a_contact()
    {
        $company = Companies::factory()->create();

        // Simulation d'une requête pour créer un nouveau contact
        $data = [
            'ordre' => 1,
            'civility' => 'Mr',
            'first_name' => 'John',
            'name' => 'Doe',
            'function' => 'Buyer',
            'number' => '0123456789',
            'mobile' => '0712345678',
            'mail' => 'john.doe@example.com',
            'companies_id' => $company->id, // ID de la compagnie associée
            'default' => 1,
        ];

        // Fais une requête POST pour créer le contact
        $response = $this->post(route('contacts.store', ['id' => $company->id]), $data);

        // Vérifie que le contact est bien créé dans la base de données
        $this->assertDatabaseHas('companies_contacts', [
            'name' => 'Doe',
            'mail' => 'john.doe@example.com',
        ]);

        // Vérifie que la redirection s'est bien faite vers la bonne route
        $response->assertRedirect(route('companies.show', ['id' => $company->id]))
                ->assertSessionHas('success', 'Successfully created contact');
    }

    /**
     * Test the update method of ContactsController.
     *
     * @return void
     */
    public function test_can_update_a_contact()
    {
        $company = Companies::factory()->create();

        // Crée un contact existant pour la mise à jour
        $contact = CompaniesContacts::factory()->create([
            'civility' => 'Mrs',
            'first_name' => 'Jane',
            'name' => 'Doe',
            'function' => 'Director',
            'number' => '0987654321',
            'mobile' => '0798765432',
            'mail' => 'jane.doe@example.com',
            'companies_id' => $company->id,
            'default' => 0,
        ]);

        // Simulation des données de la requête de mise à jour
        $data = [
            'id' => $contact->id,
            'ordre' => $contact->ordre,
            'civility' => 'Ms',
            'first_name' => 'Jane',
            'name' => 'Smith', // Nouveau nom modifié
            'function' => 'Director',
            'number' => '0123456789',
            'mobile' => '0712345678',
            'mail' => 'jane.smith@example.com',
            'companies_id' => $company->id,
            'defaultContact_update' => true, // Définit le contact par défaut
        ];

        // Fais une requête PUT pour mettre à jour le contact
        $response = $this->put(route('contacts.update', ['id' => $contact->id]), $data);

        // Vérifie que la base de données contient les données mises à jour
        $this->assertDatabaseHas('companies_contacts', [
            'id' => $contact->id,
            'name' => 'Smith',
            'mail' => 'jane.smith@example.com',
            'default' => 1, // Vérifie que le contact par défaut est bien mis à jour
        ]);

        // Vérifie que la redirection s'est bien faite vers la bonne route
        $response->assertRedirect(route('companies.show', ['id' => $company->id]))
                ->assertSessionHas('success', 'Successfully updated contact');
    }
}
