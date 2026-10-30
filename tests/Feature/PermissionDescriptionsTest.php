<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionTableSeeder;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Chaque droit livré (seeder + migrations) a une description FR et EN, affichée
 * sous son nom dans Votre société → Rôles et permissions.
 */
class PermissionDescriptionsTest extends TestCase
{
    public function test_every_shipped_permission_has_a_description_in_each_language(): void
    {
        $this->seed(PermissionTableSeeder::class);

        foreach (['fr', 'en'] as $locale) {
            $descriptions = trans('permissions.descriptions', [], $locale);
            $missing = Permission::pluck('name')
                ->reject(fn ($name) => ! empty($descriptions[$name]))
                ->values()
                ->all();

            $this->assertSame([], $missing, "Droits sans description ({$locale})");
        }
    }

    public function test_the_roles_screen_shows_descriptions_and_tolerates_unknown_permissions(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        app()->setLocale('fr');

        Permission::firstOrCreate(['name' => 'contract-review-approve', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'your-company-menu', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'droit-maison', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('your-company-menu');

        $this->actingAs($user)
            ->get(route('admin.roles.permissions'))
            ->assertOk()
            ->assertSee('droit-maison')
            ->assertSee(e(__('permissions.descriptions.contract-review-approve')), false);
    }
}
