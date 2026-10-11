<?php

namespace Tests\Feature;

use App\Models\Admin\Factory;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression tests for the two reports fixed in 2.0.2: code execution through
 * the PDF custom CSS, and administration routes (roles included) reachable by
 * any logged-in account.
 */
class AdministrationAccessTest extends TestCase
{
    use RefreshDatabase;

    // The CSS is included in <head>, where DomPDF ignores scripts: the payload
    // also has to close </head>. With the 2.0.0 settings this one executes.
    private const PHP_PAYLOAD = "x{} </style></head><body><script type=\"text/php\">file_put_contents(%s,'executed');</script><style>";

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);

        Permission::findOrCreate('your-company-menu');
        Permission::findOrCreate('human-resources-menu');
        Permission::findOrCreate('accounting-menu');
        Role::findOrCreate('Admin');
        Role::findOrCreate('Operator');
    }

    private function operator(): User
    {
        return User::factory()->create()->assignRole('Operator');
    }

    private function administrator(): User
    {
        $user = User::factory()->create()->assignRole('Admin');
        $user->givePermissionTo(['your-company-menu', 'human-resources-menu']);

        return $user;
    }

    /** The employee form posts every field; the controller writes them all. */
    private function employeeForm(User $user, array $overrides = []): array
    {
        return array_merge([
            'employment_status' => $user->employment_status ?? 1,
            'statu' => $user->statu ?? 1,
        ], $overrides);
    }

    // --- PDF custom CSS -------------------------------------------------

    public function test_php_in_the_pdf_custom_css_is_never_executed(): void
    {
        $witness = storage_path('framework/testing/witness_rce_'.uniqid().'.txt');
        @mkdir(dirname($witness), 0777, true);

        $css = sprintf(self::PHP_PAYLOAD, var_export($witness, true));
        $styles = view('print.partials.custom-styles', ['customCss' => $css])->render();

        Pdf::loadHTML('<html><head>'.$styles.'</head><body>Devis</body></html>')->output();

        $this->assertFileDoesNotExist($witness);
        $this->assertStringNotContainsString('<script', $styles);
        $this->assertStringNotContainsString('</style><', $styles);
    }

    public function test_dompdf_php_javascript_and_remote_access_are_disabled(): void
    {
        // The options DomPDF actually runs with (built from `dompdf.defines`).
        $options = app('dompdf')->getOptions();

        $this->assertFalse($options->getIsPhpEnabled());
        $this->assertFalse($options->getIsJavascriptEnabled());
        $this->assertFalse($options->getIsRemoteEnabled());
    }

    public function test_sanitizing_the_custom_css_keeps_a_real_stylesheet_intact(): void
    {
        $css = "table > tr td { color: #333; }\n.header::after { content: \"A > B\"; }";

        $this->assertSame($css, Factory::sanitizePdfCss($css));
        $this->assertNull(Factory::sanitizePdfCss(null));
    }

    // --- Administration routes -------------------------------------------

    public function test_a_regular_account_cannot_change_the_company_settings(): void
    {
        $this->actingAs($this->operator())
            ->post(route('admin.factory.update'), ['pdf_custom_css' => 'body{}'])
            ->assertForbidden();
    }

    public function test_a_regular_account_cannot_reach_roles_or_api_tokens(): void
    {
        $this->actingAs($this->operator());

        $this->get(route('admin.roles.permissions'))->assertForbidden();
        $this->post(route('admin.factory.role.store'), ['name' => 'Pwned'])->assertForbidden();
        $this->get(route('admin.api-tokens.index'))->assertForbidden();
        $this->post(route('admin.api-tokens.store'), ['name' => 'x'])->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'Pwned']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_routes_used_by_regular_screens_stay_reachable(): void
    {
        $this->actingAs($this->operator())
            ->getJson(route('admin.logs-viewer.json.meta'))
            ->assertOk();
    }

    public function test_the_fec_export_is_open_to_accounting_without_administration(): void
    {
        $accountant = $this->operator();
        $accountant->givePermissionTo('accounting-menu');

        $this->actingAs($this->operator())
            ->getJson(route('admin.fec.export.json.list'))
            ->assertForbidden();

        $this->actingAs($accountant)
            ->getJson(route('admin.fec.export.json.list'))
            ->assertOk();
    }

    // --- Role assignment --------------------------------------------------

    public function test_a_regular_account_cannot_grant_itself_the_admin_role(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)
            ->post(route('human.resources.update.user', ['id' => $operator->id]), $this->employeeForm($operator, ['role' => 'Admin']))
            ->assertForbidden();

        $this->assertFalse($operator->fresh()->hasRole('Admin'));
    }

    public function test_human_resources_cannot_hand_out_roles(): void
    {
        $hr = $this->operator();
        $hr->givePermissionTo('human-resources-menu');
        $employee = $this->operator();

        $this->actingAs($hr)
            ->post(route('human.resources.update.user', ['id' => $hr->id]), $this->employeeForm($hr, ['role' => 'Admin']))
            ->assertRedirect();
        $this->actingAs($hr)
            ->post(route('human.resources.update.user', ['id' => $employee->id]), $this->employeeForm($employee, ['role' => 'Admin']))
            ->assertRedirect();

        $this->assertFalse($hr->fresh()->hasRole('Admin'));
        $this->assertFalse($employee->fresh()->hasRole('Admin'));
        $this->assertTrue($employee->fresh()->hasRole('Operator'));
    }

    public function test_an_administrator_changes_a_role_but_never_their_own(): void
    {
        $admin = $this->administrator();
        $employee = $this->operator();

        $this->actingAs($admin)
            ->post(route('human.resources.update.user', ['id' => $employee->id]), $this->employeeForm($employee, ['role' => 'Admin']))
            ->assertRedirect();
        $this->assertSame(['Admin'], $employee->fresh()->getRoleNames()->all());

        $this->actingAs($admin)
            ->post(route('human.resources.update.user', ['id' => $admin->id]), $this->employeeForm($admin, ['role' => 'Operator']))
            ->assertRedirect();
        $this->assertTrue($admin->fresh()->hasRole('Admin'));
    }

    public function test_saving_an_employee_form_without_a_role_keeps_the_current_one(): void
    {
        $admin = $this->administrator();
        $employee = $this->operator();

        $this->actingAs($admin)
            ->post(route('human.resources.update.user', ['id' => $employee->id]), $this->employeeForm($employee, ['job_title' => 'Soudeur']))
            ->assertRedirect();

        $this->assertTrue($employee->fresh()->hasRole('Operator'));
    }

    public function test_a_regular_account_cannot_lock_a_colleague(): void
    {
        $colleague = $this->operator();

        $this->actingAs($this->operator())
            ->post(route('human.resources.lock.user', ['id' => $colleague->id]), ['banned_until' => now()->addYear()])
            ->assertForbidden();

        $this->assertNull($colleague->fresh()->banned_until);
    }
}
