<?php

namespace Tests\Feature;

use Tests\TestCase;
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
        // Simulation d'une requête pour créer un nouveau contact
        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '0123456789',
            'companies_id' => 1, // ID de la compagnie associée
            'default' => 1,
        ];

        // Fais une requête POST pour créer le contact
        $response = $this->post(route('contacts.store'), $data);

        // Vérifie que le contact est bien créé dans la base de données
        $this->assertDatabaseHas('companies_contacts', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        // Vérifie que la redirection s'est bien faite vers la bonne route
        $response->assertRedirect(route('companies.show', ['id' => 1]))
                ->assertSessionHas('success', 'Successfully created contact');
    }

    /**
     * Test the update method of ContactsController.
     *
     * @return void
     */
    public function test_can_update_a_contact()
    {
        // Crée un contact existant pour la mise à jour
        $contact = CompaniesContacts::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'phone' => '0987654321',
            'companies_id' => 1,
            'default' => 0,
        ]);

        // Simulation des données de la requête de mise à jour
        $data = [
            'id' => $contact->id,
            'name' => 'Jane Smith', // Nouveau nom modifié
            'email' => 'jane.smith@example.com',
            'phone' => '0123456789',
            'companies_id' => 1,
            'defaultContact_update' => true, // Définit le contact par défaut
        ];

        // Fais une requête PUT pour mettre à jour le contact
        $response = $this->put(route('contacts.update', ['id' => $contact->id]), $data);

        // Vérifie que la base de données contient les données mises à jour
        $this->assertDatabaseHas('companies_contacts', [
            'id' => $contact->id,
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'default' => 1, // Vérifie que le contact par défaut est bien mis à jour
        ]);

        // Vérifie que la redirection s'est bien faite vers la bonne route
        $response->assertRedirect(route('companies.show', ['id' => 1]))
                ->assertSessionHas('success', 'Successfully updated contact');
    }
}
