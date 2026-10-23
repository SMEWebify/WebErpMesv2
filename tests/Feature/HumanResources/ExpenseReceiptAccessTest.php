<?php

namespace Tests\Feature\HumanResources;

use App\Models\User;
use App\Models\UserExpenseReport;
use App\Services\Files\FileRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Expense receipts live in the GED but, like the employee folder, they are
 * restricted to the employee concerned and to HR.
 */
class ExpenseReceiptAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Storage::fake(config('files.disk'));

        Permission::findOrCreate('human-resources-menu');
    }

    private function reportOf(User $employee): UserExpenseReport
    {
        return UserExpenseReport::create([
            'user_id' => $employee->id,
            'date' => now()->toDateString(),
            'label' => 'Octobre 2026',
            'status' => 2,
        ]);
    }

    /**
     * The employee photographs a receipt; returns the file id.
     */
    private function uploadReceipt(UserExpenseReport $report, User $uploader): int
    {
        $response = $this->actingAs($uploader)->postJson(route('files.json.store'), [
            'fileable_type' => 'expense-report',
            'fileable_id' => $report->id,
            'files' => [UploadedFile::fake()->image('ticket.jpg')],
        ]);

        $response->assertCreated();

        return $response->json('files.0.id');
    }

    /** @test */
    public function an_employee_can_add_and_read_receipts_on_their_own_report()
    {
        $employee = User::factory()->create();
        $report = $this->reportOf($employee);

        $fileId = $this->uploadReceipt($report, $employee);

        $this->actingAs($employee)
            ->getJson(route('files.json.list', ['fileable_type' => 'expense-report', 'fileable_id' => $report->id]))
            ->assertOk()
            ->assertJsonPath('files.0.id', $fileId);

        $this->actingAs($employee)
            ->get(route('files.raw', ['file' => $fileId]))
            ->assertOk();

        // Left on "auto", a photo becomes a receipt rather than a product photo.
        $this->assertDatabaseHas('fileables', [
            'file_id' => $fileId,
            'fileable_type' => UserExpenseReport::class,
            'role' => FileRole::RECEIPT,
        ]);
    }

    /** @test */
    public function a_colleague_can_neither_list_nor_open_nor_add_receipts()
    {
        $employee = User::factory()->create();
        $colleague = User::factory()->create();
        $report = $this->reportOf($employee);

        $fileId = $this->uploadReceipt($report, $employee);

        $this->actingAs($colleague)
            ->getJson(route('files.json.list', ['fileable_type' => 'expense-report', 'fileable_id' => $report->id]))
            ->assertForbidden();

        $this->actingAs($colleague)
            ->get(route('files.raw', ['file' => $fileId]))
            ->assertForbidden();

        $this->actingAs($colleague)
            ->get(route('files.download', ['file' => $fileId]))
            ->assertForbidden();

        $this->actingAs($colleague)
            ->postJson(route('files.json.store'), [
                'fileable_type' => 'expense-report',
                'fileable_id' => $report->id,
                'files' => [UploadedFile::fake()->image('ticket.jpg')],
            ])
            ->assertForbidden();
    }

    /** @test */
    public function a_colleague_cannot_open_or_change_another_employee_report()
    {
        $employee = User::factory()->create();
        $colleague = User::factory()->create();
        $report = $this->reportOf($employee);

        $this->actingAs($colleague)
            ->get(route('human.resources.show.expense', ['id' => $report->id]))
            ->assertForbidden();

        $this->actingAs($colleague)
            ->post(route('human.resources.update.expense.report', ['id' => $report->id]), [
                'id' => $report->id,
                'label' => 'Piraté',
                'status' => 3,
                'date' => now()->toDateString(),
            ])
            ->assertForbidden();

        // Approving is reserved to HR, even for one's own report.
        $this->actingAs($employee)
            ->post(route('human.resources.valide.expense.report'), ['id' => $report->id, 'status' => 5])
            ->assertForbidden();

        $this->assertSame(2, (int) $report->fresh()->status);
    }

    /** @test */
    public function a_legacy_scan_is_only_served_to_the_employee_and_hr()
    {
        Storage::fake('local');

        $employee = User::factory()->create();
        $colleague = User::factory()->create();
        $hr = User::factory()->create();
        $hr->givePermissionTo('human-resources-menu');
        $report = $this->reportOf($employee);

        $filename = '0f8fad5b-d9cb-469f-a165-70867728950e.jpg';
        Storage::disk('local')->put('expenses/' . $filename, 'scan');

        $category = \App\Models\UserExpenseCategory::create(['label' => 'Repas']);
        \App\Models\UserExpense::create([
            'report_id' => $report->id,
            'user_id' => $employee->id,
            'category_id' => $category->id,
            'expense_date' => now()->toDateString(),
            'location' => 'Nantes',
            'description' => 'Déjeuner',
            'amount' => 12.5,
            'payer_id' => $employee->id,
            'tax' => 10,
            'scan_file' => $filename,
        ]);

        $route = route('human.resources.expense.file', ['filename' => $filename]);

        $this->actingAs($colleague)->get($route)->assertForbidden();
        $this->actingAs($employee)->get($route)->assertOk();
        $this->actingAs($hr)->get($route)->assertOk();
    }

    /** @test */
    public function human_resources_can_read_the_receipts()
    {
        $employee = User::factory()->create();
        $hr = User::factory()->create();
        $hr->givePermissionTo('human-resources-menu');
        $report = $this->reportOf($employee);

        $fileId = $this->uploadReceipt($report, $employee);

        $this->actingAs($hr)
            ->getJson(route('files.json.list', ['fileable_type' => 'expense-report', 'fileable_id' => $report->id]))
            ->assertOk();

        $this->actingAs($hr)
            ->get(route('files.raw', ['file' => $fileId]))
            ->assertOk();
    }
}
