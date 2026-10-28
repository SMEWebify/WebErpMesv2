<?php

namespace Database\Seeders;

use App\Models\Workflow\AffairCategory;
use Illuminate\Database\Seeder;

/**
 * Catégories d'affaire d'exemple (instrumentation industrielle). Non appelé par
 * DatabaseSeeder : les catégories sont propres à chaque société et se créent
 * dans Méthodes → Catégories d'affaire. Pour les poser sur une instance :
 *   php artisan db:seed --class=AffairCategorySeeder
 * Idempotent : une catégorie déjà présente (même code) n'est pas modifiée.
 */
class AffairCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'DEBIT',   'label' => 'Débitmétrie',      'ordre' => 10],
            ['code' => 'PYRO',    'label' => 'Pyrométrie',       'ordre' => 20],
            ['code' => 'ACCESS',  'label' => 'Accessoire',       'ordre' => 30],
            ['code' => 'MECA',    'label' => 'Mécanique',        'ordre' => 40],
            ['code' => 'SPECIAL', 'label' => 'Affaire spéciale', 'ordre' => 50],
        ];

        foreach ($categories as $category) {
            AffairCategory::firstOrCreate(['code' => $category['code']], $category + ['active' => true]);
        }
    }
}
