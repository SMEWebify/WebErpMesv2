<?php

namespace Tests\Feature\Receivables;

use App\Mail\InvoiceReminderMail;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Models\EmailLog;
use App\Models\User;
use App\Models\Workflow\CreditNoteLines;
use App\Models\Workflow\CreditNotes;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\InvoicePayment;
use App\Models\Workflow\InvoiceReminder;
use App\Models\Workflow\InvoiceReminderLevel;
use App\Models\Workflow\Invoices;
use App\Services\Documents\DocumentPdfService;
use App\Services\Receivables\InvoiceReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoiceRemindersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Companies $company;
    private CompaniesContacts $contact;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        Permission::findOrCreate('invoice-reminders-send');
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->company = Companies::factory()->create(['statu_customer' => 2, 'label' => 'Tôlerie Martin']);
        $this->contact = CompaniesContacts::factory()->create(['companies_id' => $this->company->id, 'mail' => 'compta@martin.test']);

        // La génération réelle passe par Factur-X, qui exige des parties
        // identifiables : hors sujet ici.
        $this->mock(DocumentPdfService::class, function ($mock) {
            $mock->shouldReceive('render')->andReturn('%PDF-1.4 fake');
            $mock->shouldReceive('fileName')->andReturnUsing(fn ($invoice) => $invoice->code . '.pdf');
        });
    }

    private function service(): InvoiceReminderService
    {
        return app(InvoiceReminderService::class);
    }

    /** Facture de 120 € TTC échue depuis $daysLate jours. */
    private function overdueInvoice(int $daysLate, array $attributes = []): Invoices
    {
        $invoice = Invoices::factory()->create(array_merge([
            'companies_id'          => $this->company->id,
            'companies_contacts_id' => $this->contact->id,
            'statu'                 => 2,
            'due_date'              => now()->subDays($daysLate)->toDateString(),
        ], $attributes));

        InvoiceLines::create([
            'invoices_id' => $invoice->id, 'label' => 'Prestation', 'ordre' => 10,
            'qty' => 1, 'unit_price' => 100, 'discount' => 0, 'vat_rate' => 20,
        ]);

        return $invoice;
    }

    private function remindedAt(Invoices $invoice, int $level): void
    {
        $invoice->forceFill(['reminder_level_id' => InvoiceReminderLevel::where('level', $level)->value('id')])->save();
    }

    private function proposedFor(Invoices $invoice): ?int
    {
        return $this->service()->overdue()->firstWhere('id', $invoice->id)['proposed_level'] ?? null;
    }

    public function test_default_levels_are_seeded(): void
    {
        $this->assertSame([1 => 1, 2 => 15, 3 => 30], InvoiceReminderLevel::orderBy('level')->pluck('delay_days', 'level')->all());
    }

    public function test_the_proposed_level_follows_the_delay_without_skipping(): void
    {
        $this->assertSame(1, $this->proposedFor($this->overdueInvoice(3)));
        // 40 jours de retard sans relance : on commence par le rappel.
        $this->assertSame(1, $this->proposedFor($this->overdueInvoice(40)));

        $second = $this->overdueInvoice(20);
        $this->remindedAt($second, 1);
        $this->assertSame(2, $this->proposedFor($second));

        $third = $this->overdueInvoice(31);
        $this->remindedAt($third, 2);
        $this->assertSame(3, $this->proposedFor($third));
    }

    public function test_an_invoice_already_reminded_at_its_level_is_not_proposed(): void
    {
        $recent = $this->overdueInvoice(10);
        $this->remindedAt($recent, 1);
        $last = $this->overdueInvoice(60);
        $this->remindedAt($last, 3);

        $this->assertNull($this->proposedFor($recent));
        $this->assertNull($this->proposedFor($last));
        // Toujours listées : seules les factures soldées disparaissent.
        $this->assertCount(2, $this->service()->overdue());
    }

    public function test_a_settled_invoice_is_never_proposed(): void
    {
        $paid = $this->overdueInvoice(20);
        InvoicePayment::create(['invoice_id' => $paid->id, 'amount' => 120, 'payment_date' => now()->toDateString(), 'user_id' => $this->user->id]);

        $credited = $this->overdueInvoice(20);
        $creditNote = CreditNotes::factory()->create(['invoices_id' => $credited->id, 'companies_id' => $this->company->id]);
        CreditNoteLines::create([
            'credit_note_id' => $creditNote->id, 'invoice_line_id' => $credited->invoiceLines()->first()->id,
            'qty' => 1, 'unit_price' => 100, 'discount' => 0, 'vat_rate' => 20,
        ]);

        $this->assertSame([], $this->service()->overdue()->pluck('id')->all());
    }

    public function test_drafts_proformas_and_invoices_not_yet_due_are_left_out(): void
    {
        $this->overdueInvoice(10, ['statu' => 1]);
        $this->overdueInvoice(10, ['invoice_type' => 3]);
        $this->overdueInvoice(0);
        $this->overdueInvoice(-5);

        $this->assertCount(0, $this->service()->overdue());
    }

    public function test_sending_a_grouped_reminder_traces_every_invoice(): void
    {
        Mail::fake();
        $this->user->givePermissionTo('invoice-reminders-send');
        $first  = $this->overdueInvoice(20);
        $this->remindedAt($first, 1);
        $second = $this->overdueInvoice(5);
        $payload = ['companies_id' => $this->company->id, 'invoice_ids' => [$first->id, $second->id]];

        $preview = $this->postJson(route('invoices.reminders.preview'), $payload)->assertOk();
        $this->assertSame('compta@martin.test', $preview->json('to'));
        $this->assertSame(2, $preview->json('level')); // le plus haut des deux
        $this->assertStringContainsString($first->code, $preview->json('message'));
        $this->assertStringNotContainsString('{factures}', $preview->json('message'));

        $this->postJson(route('invoices.reminders.send'), $payload + [
            'to' => $preview->json('to'), 'subject' => $preview->json('subject'), 'message' => $preview->json('message'),
        ])->assertOk()->assertJsonPath('ok', true);

        Mail::assertSent(InvoiceReminderMail::class, fn ($mail) => $mail->hasTo('compta@martin.test') && count($mail->pdfs) === 2);

        foreach ([$first, $second] as $invoice) {
            $this->assertSame(1, $invoice->emailLogs()->where('status', EmailLog::STATUS_SENT)->count());
            $reminder = InvoiceReminder::where('invoice_id', $invoice->id)->sole();
            $this->assertTrue($reminder->email_sent);
            $this->assertSame('email', $reminder->channel);
            $this->assertNotNull($invoice->fresh()->last_reminder_at);
        }
        $this->assertSame(2, $first->fresh()->reminderLevel->level);
        $this->assertSame(1, $second->fresh()->reminderLevel->level);
        $this->assertNull($this->proposedFor($second));
    }

    public function test_a_settled_invoice_cannot_be_sent(): void
    {
        Mail::fake();
        $this->user->givePermissionTo('invoice-reminders-send');
        $invoice = $this->overdueInvoice(20);
        InvoicePayment::create(['invoice_id' => $invoice->id, 'amount' => 120, 'payment_date' => now()->toDateString(), 'user_id' => $this->user->id]);

        $this->postJson(route('invoices.reminders.send'), [
            'companies_id' => $this->company->id, 'invoice_ids' => [$invoice->id],
            'to' => 'compta@martin.test', 'subject' => 'Relance', 'message' => '<p>Bonjour</p>',
        ])->assertStatus(422);

        Mail::assertNothingSent();
        $this->assertSame(0, InvoiceReminder::count());
    }

    public function test_sending_requires_the_permission(): void
    {
        Mail::fake();
        $invoice = $this->overdueInvoice(20);

        $this->postJson(route('invoices.reminders.send'), [
            'companies_id' => $this->company->id, 'invoice_ids' => [$invoice->id],
            'to' => 'compta@martin.test', 'subject' => 'Relance', 'message' => '<p>Bonjour</p>',
        ])->assertForbidden();

        Mail::assertNothingSent();
    }

    public function test_a_phone_call_is_logged_as_a_reminder(): void
    {
        $this->user->givePermissionTo('invoice-reminders-send');
        $invoice = $this->overdueInvoice(20);

        $this->postJson(route('invoices.reminders.log'), [
            'companies_id' => $this->company->id, 'invoice_ids' => [$invoice->id],
            'channel' => 'phone', 'note' => 'Promesse de paiement vendredi',
        ])->assertOk();

        $reminder = InvoiceReminder::sole();
        $this->assertSame('phone', $reminder->channel);
        $this->assertFalse($reminder->email_sent);
        $this->assertEquals(120, $reminder->amount_due);
        $this->assertSame(20, $reminder->days_late);
        $this->assertSame(1, $invoice->fresh()->reminderLevel->level);
    }

    public function test_the_screen_and_its_list_load(): void
    {
        $this->overdueInvoice(20);

        $this->get(route('invoices.reminders'))->assertOk();
        $this->getJson(route('invoices.reminders.json.list'))
            ->assertOk()
            ->assertJsonPath('data.0.company_label', 'Tôlerie Martin')
            ->assertJsonPath('data.0.proposed_level', 1)
            ->assertJsonCount(3, 'levels');
    }

    public function test_invoice_page_shows_the_reminders(): void
    {
        $invoice = $this->overdueInvoice(20);
        InvoiceReminder::create([
            'invoice_id' => $invoice->id, 'reminded_at' => now(), 'channel' => 'phone',
            'user_id' => $this->user->id, 'note' => 'Appel au service comptable',
        ]);

        $this->get(route('invoices.show', ['id' => $invoice->id]))
            ->assertOk()
            ->assertSee('Appel au service comptable');
    }
}
