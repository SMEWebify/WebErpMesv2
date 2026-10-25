<?php

namespace Tests\Feature;

use App\Models\Admin\EstimatedBudgets;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * L'assistant d'installation refusait les étapes 7 (rôle) et 8 (objectif) dès la fin de
 * l'étape 6 ; un compte sans rôle bouclait ensuite entre /setup et /dashboard.
 * TestCase crée déjà les six référentiels : on est donc « après l'étape 6 ».
 */
class SetupWizardTest extends TestCase
{
    use RefreshDatabase;

    // UserFactory donne le rôle « admin » à chaque compte : on le retire.
    private function userWithoutRole(): User
    {
        return tap(User::factory()->create(), fn (User $user) => $user->syncRoles([]));
    }

    private function budget(): array
    {
        return ['year' => now()->year] + array_combine(
            array_map(fn ($m) => "amount{$m}", range(1, 12)),
            array_fill(0, 12, 1000)
        );
    }

    /** @test */
    public function the_role_and_budget_steps_still_work_after_the_referentials()
    {
        $admin = $this->userWithoutRole();

        $this->actingAs($admin)
            ->postJson(route('setup.role'), ['role_name' => 'Admin', 'permissions' => []])
            ->assertOk();
        $this->assertTrue($admin->fresh()->hasRole('Admin'));

        $this->actingAs($admin)
            ->postJson(route('setup.estimated-budget'), $this->budget())
            ->assertOk();
        $this->assertTrue(EstimatedBudgets::where('year', now()->year)->exists());
    }

    /** @test */
    public function nobody_can_give_himself_a_role_once_an_administrator_exists()
    {
        User::factory()->create()->assignRole(Role::create(['name' => 'Admin']));
        $intruder = $this->userWithoutRole();

        $this->actingAs($intruder)
            ->postJson(route('setup.role'), ['role_name' => 'Admin', 'permissions' => ['your-company-menu']])
            ->assertForbidden();
        $this->assertFalse($intruder->fresh()->hasAnyRole(Role::all()));
    }

    /** @test */
    public function the_budget_step_closes_once_a_budget_exists()
    {
        EstimatedBudgets::create($this->budget());

        $this->actingAs(User::factory()->create())
            ->postJson(route('setup.estimated-budget'), ['year' => now()->year + 1] + $this->budget())
            ->assertForbidden();
    }

    /** @test */
    public function the_company_steps_stay_closed_after_the_referentials()
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('setup.vat'), ['code' => 'X', 'label' => 'X', 'rate' => 5])
            ->assertForbidden();
    }

    /** @test */
    public function a_user_without_role_on_an_installed_instance_waits_instead_of_looping()
    {
        User::factory()->create()->assignRole(Role::create(['name' => 'Admin']));

        $this->actingAs($this->userWithoutRole())
            ->get(route('dashboard'))
            ->assertRedirect(route('pending.role'));
    }

    /** @test */
    public function on_a_first_install_the_user_without_role_is_sent_to_the_wizard()
    {
        $this->actingAs($this->userWithoutRole())
            ->get(route('dashboard'))
            ->assertRedirect(route('setup.index'));
    }
}
