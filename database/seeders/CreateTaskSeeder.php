<?php

namespace Database\Seeders;

use App\Models\Planning\Task;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CreateTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Créer 5000 tâches en utilisant la factory
        Task::factory()->count(5000)->create();
    }
}
