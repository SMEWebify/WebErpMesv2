<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserExpense;
use App\Models\UserExpenseCategory;
use App\Models\UserExpenseReport;
use App\Models\Admin\UserEmploymentContracts;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HumanResourcesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        // Créer un utilisateur authentifié
        $this->user = User::factory()->create();
        $this->actingAs($this->user); // Authentification utilisateur
    }

    /** @test */
    public function it_displays_the_human_resources_index_page()
    {
        // Préparer les données nécessaires
        User::factory(10)->create();
        UserExpenseReport::factory(5)->create(['status' => 3]);
        UserExpenseCategory::factory(5)->create();

        // Appel de la route et vérification de la vue
        $response = $this->get(route('human.resources.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin/human-resources-index');
        $response->assertViewHas('Users');
        $response->assertViewHas('ExpenseReports');
    }

    /** @test */
    public function it_displays_the_user_details_page()
    {
        $user = User::factory()->create();
        $response = $this->get(route('human.resources.show.user', ['id' => $user->id]));

        $response->assertStatus(200);
        $response->assertViewIs('admin/users-show');
        $response->assertViewHas('User');
    }

    /** @test */
    public function it_updates_a_user()
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Manager']);
        
        $response = $this->put(route('human.resources.update.user', $user->id), [
            'job_title' => 'Developer',
            'pay_grade' => 'Level 1',
            'role' => $role->name
        ]);

        $response->assertRedirect(route('human.resources.show.user', ['id' => $user->id]));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'job_title' => 'Developer',
        ]);
    }

    /** @test */
    public function it_locks_a_user()
    {
        $user = User::factory()->create();

        $response = $this->post(route('human.resources.lock.user', $user->id), [
            'banned_until' => now()->addDays(10),
        ]);

        $response->assertRedirect(route('human.resources.show.user', ['id' => $user->id]));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'banned_until' => now()->addDays(10),
        ]);
    }

    /** @test */
    public function it_creates_a_user_employment_contract()
    {
        $user = User::factory()->create();
        
        $response = $this->post(route('human.resources.store.user.contract'), [
            'user_id' => $user->id,
            'statu' => 'Active',
            'type_of_contract' => 'Permanent',
            'start_date' => now(),
        ]);

        $response->assertRedirect(route('human.resources.show.user', ['id' => $user->id]));
        $this->assertDatabaseHas('user_employment_contracts', [
            'user_id' => $user->id,
            'statu' => 'Active',
        ]);
    }

    /** @test */
    public function it_updates_a_user_employment_contract()
    {
        $contract = UserEmploymentContracts::factory()->create();

        $response = $this->put(route('human.resources.update.user.contract', $contract->id), [
            'statu' => 'Inactive',
        ]);

        $response->assertRedirect(route('human.resources.show.user', ['id' => $contract->user_id]));
        $this->assertDatabaseHas('user_employment_contracts', [
            'id' => $contract->id,
            'statu' => 'Inactive',
        ]);
    }

    /** @test */
    public function it_creates_a_user_expense_category()
    {
        $response = $this->post(route('human.resources.store.user.expense.category'), [
            'label' => 'Travel',
            'description' => 'Travel expenses',
        ]);

        $response->assertRedirect(route('human.resources'));
        $this->assertDatabaseHas('user_expense_categories', [
            'label' => 'Travel',
        ]);
    }

    /** @test */
    public function it_updates_a_user_expense_category()
    {
        $category = UserExpenseCategory::factory()->create();

        $response = $this->put(route('human.resources.update.user.expense.category', $category->id), [
            'label' => 'Updated Label',
            'description' => 'Updated Description',
        ]);

        $response->assertRedirect(route('human.resources'));
        $this->assertDatabaseHas('user_expense_categories', [
            'id' => $category->id,
            'label' => 'Updated Label',
        ]);
    }

    /** @test */
    public function it_creates_a_user_expense_report()
    {
        $response = $this->post(route('human.resources.store.user.expense.report'), [
            'date' => now(),
            'label' => 'Expense Report 1',
        ]);

        $response->assertRedirect(route('user.profile', ['id' => $this->user->id]));
        $this->assertDatabaseHas('user_expense_reports', [
            'label' => 'Expense Report 1',
        ]);
    }

    /** @test */
    public function it_updates_a_user_expense_report()
    {
        $report = UserExpenseReport::factory()->create();

        $response = $this->put(route('human.resources.update.user.expense.report', $report->id), [
            'label' => 'Updated Report',
            'status' => 1,
        ]);

        $response->assertRedirect(route('user.profile', ['id' => $report->user_id]));
        $this->assertDatabaseHas('user_expense_reports', [
            'id' => $report->id,
            'label' => 'Updated Report',
        ]);
    }
}
