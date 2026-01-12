<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Planning\Task;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test the index method of TaskController.
     *
     * @return void
     */
    public function test_can_get_all_tasks()
    {
        // Crée plusieurs tâches (tasks)
        Task::factory()->count(5)->create();

        // Fais une requête GET pour obtenir toutes les tâches
        $this->authenticateApiUser();
        $response = $this->getJson('/api/tasks');

        // Vérifie que la réponse a le statut 200 (succès)
        $response->assertStatus(200);

        // Vérifie que la réponse contient exactement 5 tâches
        $response->assertJsonCount(5, 'data');
    }

    /**
     * Test the show method of TaskController.
     *
     * @return void
     */
    public function test_can_show_a_task()
    {
        // Crée une tâche (Task)
        $task = Task::factory()->create();

        // Fais une requête GET pour afficher cette tâche spécifique
        $this->authenticateApiUser();
        $response = $this->getJson("/api/tasks/{$task->id}");

        // Vérifie que la réponse a le statut 200 (succès)
        $response->assertStatus(200);

        // Vérifie que la réponse contient les données de la tâche
        $response->assertJson([
            'data' => [
                'id' => $task->id,
                'name' => $task->name, // Supposons que "name" soit un attribut de Task
                // Ajoute d'autres attributs si nécessaire
            ],
        ]);
    }
}
