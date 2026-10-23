<?php

namespace Tests\Feature\HumanResources;

use App\Models\User;
use App\Models\UserExpense;
use App\Models\UserExpenseCategory;
use App\Models\UserExpenseReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A receipt is read by the AI provider to pre-fill the expense form; nothing
 * is saved until the employee submits the form.
 */
class ExpenseReceiptReadingTest extends TestCase
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

        config([
            'ai.providers.claude.api_key' => 'test-key',
            'ai.providers.claude.default_model' => 'claude-haiku-4-5-20251001',
        ]);
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

    private function uploadReceipt(UserExpenseReport $report, User $uploader): int
    {
        return $this->actingAs($uploader)->postJson(route('files.json.store'), [
            'fileable_type' => 'expense-report',
            'fileable_id' => $report->id,
            'files' => [UploadedFile::fake()->image('ticket.jpg', 2400, 3200)],
        ])->assertCreated()->json('files.0.id');
    }

    private function fakeClaude(string $answer): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'model' => 'claude-haiku-4-5-20251001',
                'content' => [['type' => 'text', 'text' => $answer]],
                'usage' => ['input_tokens' => 10, 'output_tokens' => 10],
            ]),
        ]);
    }

    /** @test */
    public function it_proposes_the_expense_fields_read_from_a_receipt()
    {
        $employee = User::factory()->create();
        $report = $this->reportOf($employee);
        $meal = UserExpenseCategory::create(['label' => 'Repas']);
        UserExpenseCategory::create(['label' => 'Carburant']);
        $fileId = $this->uploadReceipt($report, $employee);

        $this->fakeClaude("```json\n" . json_encode([
            'date' => '2026-10-21',
            'total_ttc' => '27,50',
            'vat_rate' => 10,
            'merchant' => 'Brasserie du Port',
            'city' => 'Nantes',
            'description' => 'Déjeuner client',
            'category' => 'repas',
        ]) . "\n```");

        $this->actingAs($employee)
            ->postJson(route('human.resources.expense.receipts.read', ['report_id' => $report->id, 'file_id' => $fileId]))
            ->assertOk()
            ->assertJsonPath('expense.expense_date', '2026-10-21')
            ->assertJsonPath('expense.amount', 27.5)
            ->assertJsonPath('expense.tax', 10)
            ->assertJsonPath('expense.location', 'Brasserie du Port - Nantes')
            ->assertJsonPath('expense.description', 'Déjeuner client')
            ->assertJsonPath('expense.category_id', $meal->id);

        // The photo is sent shrunk, as an image block, and nothing is saved.
        Http::assertSent(function (HttpRequest $request) {
            $block = $request['messages'][0]['content'][0] ?? [];
            if (($block['type'] ?? null) !== 'image') {
                return false;
            }
            [$width, $height] = getimagesizefromstring(base64_decode($block['source']['data']));

            return max($width, $height) <= 1600;
        });
        $this->assertSame(0, UserExpense::count());
    }

    /** @test */
    public function an_unreadable_answer_is_reported_instead_of_guessed()
    {
        $employee = User::factory()->create();
        $report = $this->reportOf($employee);
        $fileId = $this->uploadReceipt($report, $employee);

        $this->fakeClaude('Je ne parviens pas à lire ce document.');

        $this->actingAs($employee)
            ->postJson(route('human.resources.expense.receipts.read', ['report_id' => $report->id, 'file_id' => $fileId]))
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    /** @test */
    public function a_colleague_cannot_have_a_receipt_read()
    {
        $employee = User::factory()->create();
        $colleague = User::factory()->create();
        $report = $this->reportOf($employee);
        $fileId = $this->uploadReceipt($report, $employee);

        Http::fake();

        $this->actingAs($colleague)
            ->postJson(route('human.resources.expense.receipts.read', ['report_id' => $report->id, 'file_id' => $fileId]))
            ->assertForbidden();

        $this->actingAs($colleague)
            ->getJson(route('human.resources.expense.receipts.json', ['report_id' => $report->id]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    /** @test */
    public function a_receipt_of_another_report_cannot_be_read_through_mine()
    {
        $employee = User::factory()->create();
        $mine = $this->reportOf($employee);
        $other = $this->reportOf(User::factory()->create());
        $foreignFileId = $this->uploadReceipt($other, $other->user);

        Http::fake();

        $this->actingAs($employee)
            ->postJson(route('human.resources.expense.receipts.read', ['report_id' => $mine->id, 'file_id' => $foreignFileId]))
            ->assertNotFound();

        Http::assertNothingSent();
    }

    /** @test */
    public function a_saved_line_is_linked_to_its_receipt_which_leaves_the_list()
    {
        $employee = User::factory()->create();
        $report = $this->reportOf($employee);
        $category = UserExpenseCategory::create(['label' => 'Repas']);
        $fileId = $this->uploadReceipt($report, $employee);
        $foreignFileId = $this->uploadReceipt($other = $this->reportOf($employee), $employee);

        $this->actingAs($employee)
            ->getJson(route('human.resources.expense.receipts.json', ['report_id' => $report->id]))
            ->assertOk()
            ->assertJsonPath('receipts.0.id', $fileId);

        $line = [
            'category_id' => $category->id,
            'expense_date' => '2026-10-21',
            'location' => 'Nantes',
            'description' => 'Déjeuner',
            'amount' => 27.5,
            'payer_id' => $employee->id,
            'tax' => 10,
        ];

        $this->actingAs($employee)
            ->post(route('human.resources.create.expense.line', ['report_id' => $report->id]), $line + ['file_id' => $fileId])
            ->assertRedirect();

        // A receipt of another report is never linked.
        $this->actingAs($employee)
            ->post(route('human.resources.create.expense.line', ['report_id' => $report->id]), $line + ['file_id' => $foreignFileId])
            ->assertRedirect();

        $this->assertSame([$fileId, null], UserExpense::orderBy('id')->pluck('file_id')->all());

        $this->actingAs($employee)
            ->getJson(route('human.resources.expense.receipts.json', ['report_id' => $report->id]))
            ->assertOk()
            ->assertJsonCount(0, 'receipts');
    }
}
