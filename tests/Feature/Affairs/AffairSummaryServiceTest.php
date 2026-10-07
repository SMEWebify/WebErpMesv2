<?php

namespace Tests\Feature\Affairs;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\EmailLog;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\Purchases;
use App\Models\User;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\InvoicePayment;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\OpportunityVisits;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Affairs\AffairSummaryService;
use App\Services\Affairs\AffairTimelineService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Synthèse d'affaire : une affaire vide, une affaire complète et chaque règle
 * de blocage, avec son cas limite qui ne doit PAS déclencher l'alerte.
 *
 * Date de référence figée au mercredi 7 octobre 2026, 12h.
 */
class AffairSummaryServiceTest extends TestCase
{
    private User $user;
    private Companies $company;
    private Opportunities $opportunity;
    private AffairSummaryService $service;
    private Carbon $now;
    private array $statuses = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = Carbon::parse('2026-10-07 12:00:00');
        Carbon::setTestNow($this->now);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->company = Companies::factory()->create(['statu_customer' => 2]);
        $this->opportunity = Opportunities::factory()->create([
            'companies_id'           => $this->company->id,
            'companies_contacts_id'  => CompaniesContacts::factory()->create(['companies_id' => $this->company->id])->id,
            'companies_addresses_id' => CompaniesAddresses::factory()->create(['companies_id' => $this->company->id])->id,
            'user_id'                => $this->user->id,
            'leads_id'               => null,
            'statu'                  => 1,
        ]);

        foreach (['Open', 'In progress', 'Finished', 'Suspended'] as $order => $title) {
            $this->statuses[$title] = Status::create(['title' => $title, 'order' => $order])->id;
        }

        $this->service = new AffairSummaryService();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Fabriques
    // ------------------------------------------------------------------

    private function summary(?array $sections = null): array
    {
        return $this->service->summarize($this->opportunity->fresh(), $sections, $this->now);
    }

    private function blockerCodes(array $summary): array
    {
        return array_column($summary['blockers'], 'code');
    }

    private function quote(array $attributes = [], float $amount = 1000): Quotes
    {
        $quote = Quotes::factory()->create($attributes + [
            'opportunities_id' => $this->opportunity->id,
            'companies_id'     => $this->company->id,
            'statu'            => 1,
            'validity_date'    => '2026-11-30',
        ]);
        QuoteLines::factory()->create([
            'quotes_id' => $quote->id, 'qty' => 1, 'selling_price' => $amount, 'discount' => 0,
        ]);

        return $quote;
    }

    private function order(Quotes $quote, array $attributes = [], array $line = []): OrderLines
    {
        $order = Orders::factory()->create($attributes + [
            'quotes_id'     => $quote->id,
            'companies_id'  => $this->company->id,
            'type'          => 1,
            'statu'         => 1,
            'validity_date' => '2026-12-15',
        ]);

        return OrderLines::factory()->create($line + [
            'orders_id'       => $order->id,
            'qty'             => 2,
            'selling_price'   => 500,
            'discount'        => 0,
            'delivered_qty'   => 0,
            'delivery_status' => 1,
            'invoice_status'  => 1,
            'delivery_date'   => '2026-12-15',
        ]);
    }

    private function task(OrderLines $line, string $status = 'Open', array $attributes = []): Task
    {
        return Task::factory()->create($attributes + [
            'order_lines_id' => $line->id,
            'quote_lines_id' => null,
            'status_id'      => $this->statuses[$status],
            'seting_time'    => 1,
            'unit_time'      => 0.5,
        ]);
    }

    private function purchaseLine(Task $task, int $purchaseStatu, array $attributes = []): PurchaseLines
    {
        $purchase = Purchases::factory()->create(['statu' => $purchaseStatu]);

        return PurchaseLines::create($attributes + [
            'purchases_id'              => $purchase->id,
            'tasks_id'                  => $task->id,
            'ordre'                     => 1,
            'label'                     => 'Tube 40x40',
            'qty'                       => 10,
            'selling_price'             => 20,
            'discount'                  => 0,
            'unit_price_after_discount' => 20,
            'total_selling_price'       => 200,
            'receipt_qty'               => 0,
            'delivery_date'             => '2026-10-20',
        ]);
    }

    private function invoice(OrderLines $line, array $attributes = [], float $qty = 2, float $vatRate = 20): Invoices
    {
        $invoice = Invoices::factory()->create($attributes + [
            'companies_id' => $this->company->id,
            'order_id'     => $line->orders_id,
            'invoice_type' => 1,
            'statu'        => 2,
            'due_date'     => '2026-11-07',
        ]);
        InvoiceLines::create([
            'invoices_id'   => $invoice->id,
            'order_line_id' => $line->id,
            'ordre'         => 1,
            'qty'           => $qty,
            'unit_price'    => 500,
            'discount'      => 0,
            'vat_rate'      => $vatRate,
        ]);

        return $invoice;
    }

    private function visit(int $statu, string $visitedAt): OpportunityVisits
    {
        return $this->opportunity->visits()->create([
            'user_id' => $this->user->id, 'visited_at' => $visitedAt, 'statu' => $statu,
        ]);
    }

    // ------------------------------------------------------------------
    // Affaire vide / complète
    // ------------------------------------------------------------------

    public function test_an_empty_affair_is_at_the_visit_stage_with_nothing_to_report(): void
    {
        $summary = $this->summary();

        $this->assertSame('visit', $summary['stage']);
        $this->assertSame([], $summary['blockers']);
        $this->assertSame('plan_visit', $summary['next_action']['code']);
        foreach (['quoted', 'ordered', 'purchased', 'hours_planned', 'hours_actual', 'invoiced', 'collected', 'outstanding'] as $key) {
            $this->assertEquals(0, $summary['totals'][$key], $key);
        }
        $this->assertSame(['quotes' => 0, 'orders' => 0, 'purchases' => 0, 'invoices' => 0, 'visits' => 0, 'tasks' => 0], $summary['counts']);
    }

    public function test_a_complete_affair_is_closed_with_exact_amounts(): void
    {
        $this->visit(OpportunityVisits::STATU_VALIDATED, '2026-09-01 09:00:00');
        $quote = $this->quote(['statu' => 3], 1000);
        $this->quote(['statu' => 4], 9999); // variante perdue : hors « devisé »
        $line = $this->order($quote, ['statu' => 3], ['delivery_status' => 3, 'delivered_qty' => 2, 'invoice_status' => 3]);

        $task = $this->task($line, 'Finished');
        TaskActivities::create(['task_id' => $task->id, 'user_id' => $this->user->id, 'type' => TaskActivities::TYPE_START, 'timestamp' => '2026-09-10 08:00:00']);
        TaskActivities::create(['task_id' => $task->id, 'user_id' => $this->user->id, 'type' => TaskActivities::TYPE_FINISH, 'timestamp' => '2026-09-10 10:30:00']);
        $this->purchaseLine($task, 4, ['receipt_qty' => 10]);

        $invoice = $this->invoice($line, ['statu' => 5]);
        InvoicePayment::create(['invoice_id' => $invoice->id, 'amount' => 1200, 'payment_date' => '2026-10-01', 'user_id' => $this->user->id]);

        $summary = $this->summary();

        $this->assertSame('closed', $summary['stage']);
        $this->assertSame([], $summary['blockers']);
        $this->assertNull($summary['next_action']);
        $this->assertEquals(1000, $summary['totals']['quoted']);
        $this->assertEquals(1000, $summary['totals']['ordered']);
        $this->assertEquals(200, $summary['totals']['purchased']);
        $this->assertEquals(2, $summary['totals']['hours_planned']); // 1 h de réglage + 0,5 h × 2
        $this->assertEquals(2.5, $summary['totals']['hours_actual']);
        $this->assertEquals(1000, $summary['totals']['invoiced']);
        $this->assertEquals(1200, $summary['totals']['invoiced_ttc']);
        $this->assertEquals(1200, $summary['totals']['collected']);
        $this->assertEquals(0, $summary['totals']['outstanding']);
        $this->assertSame(100, $summary['sections']['orders'][0]['delivered_pct']);
    }

    public function test_the_query_count_does_not_grow_with_the_affair(): void
    {
        $build = function (int $orders) {
            $quote = $this->quote(['statu' => 3]);
            for ($i = 0; $i < $orders; $i++) {
                $line = $this->order($quote);
                $task = $this->task($line);
                $this->purchaseLine($task, 2);
                $this->invoice($line);
            }
        };

        $count = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->summary();
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();
            return $queries;
        };

        $build(1);
        $small = $count();
        $build(3);
        $large = $count();

        $this->assertSame($small, $large);
        $this->assertLessThanOrEqual(10, $large);
    }

    // ------------------------------------------------------------------
    // Étapes
    // ------------------------------------------------------------------

    public function test_stage_follows_the_documents(): void
    {
        $this->visit(OpportunityVisits::STATU_VALIDATED, '2026-10-01 09:00:00');
        $this->assertSame('definition', $this->summary()['stage']);
        $this->assertSame('create_quote', $this->summary()['next_action']['code']);

        $quote = $this->quote(['statu' => 1]);
        $this->assertSame('quote', $this->summary()['stage']);
        $this->assertSame('send_quote', $this->summary()['next_action']['code']);

        $quote->update(['statu' => 3]);
        $this->assertSame('acceptance', $this->summary()['stage']);
        $this->assertSame('convert_quote', $this->summary()['next_action']['code']);

        $line = $this->order($quote);
        $this->assertSame('procurement', $this->summary()['stage']);

        $task = $this->task($line, 'In progress');
        TaskActivities::create(['task_id' => $task->id, 'user_id' => $this->user->id, 'type' => TaskActivities::TYPE_START, 'timestamp' => '2026-10-07 08:00:00']);
        $this->assertSame('production', $this->summary()['stage']);
        $this->assertEquals(4, $this->summary()['totals']['hours_actual']); // pointage ouvert compté jusqu'à midi

        $line->update(['delivery_status' => 3, 'delivered_qty' => 2]);
        $this->assertSame('invoicing', $this->summary()['stage']);
        $this->assertSame('invoice', $this->summary()['next_action']['code']);
    }

    public function test_an_affair_whose_quotes_are_all_lost_is_lost(): void
    {
        $this->quote(['statu' => 4]);

        $this->assertSame('lost', $this->summary()['stage']);
    }

    // ------------------------------------------------------------------
    // Règles de blocage
    // ------------------------------------------------------------------

    public function test_sent_quote_without_answer_is_flagged_after_the_follow_up_delay(): void
    {
        $quote = $this->quote(['statu' => 2]);
        EmailLog::forceCreate([
            'emailable_type' => Quotes::class, 'emailable_id' => $quote->id, 'to' => 'client@example.test',
            'subject' => 'Devis', 'message' => '…', 'status' => 'sent', 'sent_at' => '2026-09-25 10:00:00',
        ]);

        $summary = $this->summary();
        $this->assertSame(['quote_unanswered'], $this->blockerCodes($summary));
        $this->assertSame(12, $summary['blockers'][0]['params']['days']);
        $this->assertSame('follow_up_quote', $summary['next_action']['code']);
        $this->assertSame($quote->code, $summary['next_action']['ref']);
    }

    public function test_recently_sent_quote_is_not_flagged(): void
    {
        $quote = $this->quote(['statu' => 2]);
        EmailLog::forceCreate([
            'emailable_type' => Quotes::class, 'emailable_id' => $quote->id, 'to' => 'client@example.test',
            'subject' => 'Devis', 'message' => '…', 'status' => 'sent', 'sent_at' => '2026-10-02 10:00:00',
        ]);

        $this->assertSame([], $this->summary()['blockers']);
    }

    public function test_expired_quote_is_flagged(): void
    {
        $this->quote(['statu' => 1, 'validity_date' => '2026-09-30']);

        $summary = $this->summary();
        $this->assertSame(['quote_expired'], $this->blockerCodes($summary));
        $this->assertSame('danger', $summary['blockers'][0]['severity']);
        $this->assertSame('2026-09-30', $summary['blockers'][0]['date']);
    }

    public function test_expired_quote_already_converted_is_not_flagged(): void
    {
        $quote = $this->quote(['statu' => 2, 'validity_date' => '2026-09-30']);
        $this->order($quote);

        $this->assertNotContains('quote_expired', $this->blockerCodes($this->summary()));
    }

    public function test_purchase_past_its_delivery_date_is_flagged(): void
    {
        $task = $this->task($this->order($this->quote(['statu' => 3])));
        $this->purchaseLine($task, 2, ['delivery_date' => '2026-10-01', 'receipt_qty' => 4]);

        $this->assertSame(['purchase_late'], $this->blockerCodes($this->summary()));
    }

    public function test_fully_received_or_canceled_purchase_is_not_flagged(): void
    {
        $task = $this->task($this->order($this->quote(['statu' => 3])));
        $this->purchaseLine($task, 4, ['delivery_date' => '2026-10-01', 'receipt_qty' => 10]);
        $this->purchaseLine($task, 5, ['delivery_date' => '2026-10-01', 'receipt_qty' => 2]);

        $this->assertSame([], $this->summary()['blockers']);
    }

    public function test_partial_receipt_is_flagged(): void
    {
        $task = $this->task($this->order($this->quote(['statu' => 3])));
        $this->purchaseLine($task, 3, ['delivery_date' => '2026-10-20', 'receipt_qty' => 4]);

        $summary = $this->summary();
        $this->assertSame(['purchase_partial'], $this->blockerCodes($summary));
        $this->assertEquals(4, $summary['blockers'][0]['params']['received']);
    }

    public function test_suspended_task_is_flagged(): void
    {
        $line = $this->order($this->quote(['statu' => 3]));
        $this->task($line, 'Suspended', ['label' => 'Pliage']);
        $this->task($line, 'In progress');

        $summary = $this->summary();
        $this->assertSame(['task_suspended'], $this->blockerCodes($summary));
        $this->assertSame('Pliage', $summary['blockers'][0]['params']['label']);
    }

    public function test_late_order_is_flagged(): void
    {
        $quote = $this->quote(['statu' => 3]);
        $this->order($quote, [], ['delivery_date' => '2026-10-01']);

        $summary = $this->summary();
        $this->assertSame(['order_late'], $this->blockerCodes($summary));
        $this->assertSame(6, $summary['blockers'][0]['params']['days']);
    }

    public function test_delivered_or_canceled_order_is_not_late(): void
    {
        $quote = $this->quote(['statu' => 3]);
        $this->order($quote, [], ['delivery_date' => '2026-10-01', 'delivery_status' => 3]);
        $this->order($quote, ['statu' => 6], ['delivery_date' => '2026-10-01']);

        $this->assertSame([], $this->summary()['blockers']);
    }

    public function test_overdue_unpaid_invoice_is_flagged(): void
    {
        $line = $this->order($this->quote(['statu' => 3]), [], ['delivery_status' => 3]);
        $invoice = $this->invoice($line, ['statu' => 3, 'due_date' => '2026-09-30']);
        InvoicePayment::create(['invoice_id' => $invoice->id, 'amount' => 200, 'payment_date' => '2026-09-15', 'user_id' => $this->user->id]);

        $summary = $this->summary();
        $this->assertSame(['invoice_overdue'], $this->blockerCodes($summary));
        $this->assertEquals(1000, $summary['blockers'][0]['params']['amount']); // 1 200 TTC − 200 réglés
        $this->assertSame('chase_payment', $summary['next_action']['code']);
    }

    public function test_paid_or_not_yet_due_invoice_is_not_flagged(): void
    {
        $line = $this->order($this->quote(['statu' => 3]), [], ['delivery_status' => 3]);
        $this->invoice($line, ['statu' => 4, 'due_date' => '2026-11-30']);
        $this->invoice($line, ['statu' => 5, 'due_date' => '2026-09-30']);

        $this->assertSame([], $this->summary()['blockers']);
    }

    public function test_draft_visit_is_flagged_after_the_delay(): void
    {
        $this->visit(OpportunityVisits::STATU_DRAFT, '2026-10-02 09:00:00');
        $this->visit(OpportunityVisits::STATU_DRAFT, '2026-10-07 09:00:00'); // ce matin : pas encore

        $summary = $this->summary();
        $this->assertSame(['visit_unvalidated'], $this->blockerCodes($summary));
        $this->assertSame('finish_visit', $summary['next_action']['code']);
    }

    public function test_blockers_are_ordered_by_severity(): void
    {
        $this->visit(OpportunityVisits::STATU_DRAFT, '2026-10-01 09:00:00');
        $this->quote(['statu' => 1, 'validity_date' => '2026-09-30']);

        $this->assertSame(['quote_expired', 'visit_unvalidated'], $this->blockerCodes($this->summary()));
    }

    // ------------------------------------------------------------------
    // Sections masquées
    // ------------------------------------------------------------------

    public function test_hidden_sections_drop_their_blockers_amounts_and_rows(): void
    {
        $line = $this->order($this->quote(['statu' => 3]), [], ['delivery_status' => 3]);
        $this->invoice($line, ['statu' => 4, 'due_date' => '2026-09-30']);

        $summary = $this->summary(['quotes', 'orders']);

        $this->assertArrayNotHasKey('invoices', $summary['sections']);
        $this->assertNull($summary['totals']['invoiced']);
        $this->assertNotContains('invoice_overdue', $this->blockerCodes($summary));
        $this->assertSame('invoicing', $summary['stage']);
    }

    // ------------------------------------------------------------------
    // Timeline
    // ------------------------------------------------------------------

    public function test_timeline_gathers_the_affair_documents_newest_first(): void
    {
        Carbon::setTestNow('2026-09-01 08:00:00');
        $quote = $this->quote(['statu' => 3]);
        Carbon::setTestNow('2026-09-05 08:00:00');
        $line = $this->order($quote);
        Carbon::setTestNow('2026-09-20 08:00:00');
        $invoice = $this->invoice($line);
        InvoicePayment::create(['invoice_id' => $invoice->id, 'amount' => 300, 'payment_date' => '2026-09-25', 'user_id' => $this->user->id]);
        Carbon::setTestNow($this->now);

        $types = array_column((new AffairTimelineService())->timeline($this->opportunity->fresh()), 'type');

        foreach (['opportunity', 'quote', 'order', 'invoice', 'payment'] as $type) {
            $this->assertContains($type, $types);
        }
        $this->assertLessThan(array_search('quote', $types), array_search('payment', $types));

        $restricted = array_column((new AffairTimelineService())->timeline($this->opportunity->fresh(), ['quotes']), 'type');
        $this->assertNotContains('invoice', $restricted);
        $this->assertNotContains('order', $restricted);
    }

    public function test_task_log_time_pairs_start_and_stop(): void
    {
        $task = $this->task($this->order($this->quote(['statu' => 3])));
        TaskActivities::create(['task_id' => $task->id, 'user_id' => $this->user->id, 'type' => TaskActivities::TYPE_START, 'timestamp' => '2026-10-06 08:00:00']);
        TaskActivities::create(['task_id' => $task->id, 'user_id' => $this->user->id, 'type' => TaskActivities::TYPE_END, 'timestamp' => '2026-10-06 09:30:00']);

        // Régression Carbon 3 : l'écart signé était ramené à 0 par max(0, …).
        $this->assertEquals(1.5, $task->fresh()->getTotalLogTime());
    }
}
