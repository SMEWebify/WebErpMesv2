<?php

namespace Tests\Feature\Receivables;

use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\User;
use App\Models\Workflow\CreditNoteLines;
use App\Models\Workflow\CreditNotes;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\InvoicePayment;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Receivables\ReceivablesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReceivablesServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReceivablesService $service;
    private Companies $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->service = app(ReceivablesService::class);
        $this->company = Companies::factory()->create(['statu_customer' => 2]);
    }

    /** Facture d'une ligne libre : $ht HT, TVA 20 %. */
    private function invoice(float $ht, array $attributes = []): Invoices
    {
        $invoice = Invoices::factory()->create(array_merge([
            'companies_id' => $this->company->id,
            'statu'        => 2,
            'due_date'     => now()->subDays(10)->toDateString(),
        ], $attributes));

        InvoiceLines::create([
            'invoices_id' => $invoice->id,
            'label'       => 'Prestation',
            'ordre'       => 10,
            'qty'         => 1,
            'unit_price'  => $ht,
            'discount'    => 0,
            'vat_rate'    => 20,
        ]);

        return $invoice;
    }

    private function pay(Invoices $invoice, float $amount): void
    {
        InvoicePayment::create([
            'invoice_id'   => $invoice->id,
            'amount'       => $amount,
            'payment_date' => now()->toDateString(),
            'user_id'      => auth()->id(),
        ]);
    }

    /** Avoir d'une ligne : $ht HT, TVA 20 %. */
    private function creditNote(Invoices $invoice, float $ht, int $statu = 1): CreditNotes
    {
        $creditNote = CreditNotes::factory()->create(['invoices_id' => $invoice->id, 'companies_id' => $invoice->companies_id, 'statu' => $statu]);
        CreditNoteLines::create([
            'credit_note_id'  => $creditNote->id,
            'invoice_line_id' => $invoice->invoiceLines()->first()->id,
            'qty'             => 1,
            'unit_price'      => $ht,
            'discount'        => 0,
            'vat_rate'        => 20,
        ]);

        return $creditNote;
    }

    private function balanceOf(Invoices $invoice): ?float
    {
        $row = DB::query()->fromSub($this->service->invoiceBalances(includeDrafts: true), 'b')->where('b.id', $invoice->id)->first();

        return $row ? round((float) $row->balance, 2) : null;
    }

    public function test_partial_payments_reduce_the_balance(): void
    {
        $invoice = $this->invoice(1000); // 1 200 TTC
        $this->pay($invoice, 200);
        $this->pay($invoice, 150.5);

        $this->assertSame(849.5, $this->balanceOf($invoice));
        $this->assertSame(849.5, $this->service->exposure($this->company->id)->invoicesDue);
    }

    public function test_a_credit_note_is_deducted_from_its_invoice(): void
    {
        $invoice = $this->invoice(1000);
        $this->creditNote($invoice, 100); // 120 TTC
        $this->pay($invoice, 500);

        $this->assertSame(580.0, $this->balanceOf($invoice));
    }

    public function test_a_refused_credit_note_is_not_deducted(): void
    {
        $invoice = $this->invoice(1000);
        $this->creditNote($invoice, 100, statu: 3);

        $this->assertSame(1200.0, $this->balanceOf($invoice));
    }

    public function test_an_overpaid_invoice_does_not_offset_another_one(): void
    {
        $overpaid = $this->invoice(100); // 120 TTC
        $this->pay($overpaid, 150);
        $this->invoice(100);

        $this->assertSame(-30.0, $this->balanceOf($overpaid));
        $this->assertSame(120.0, $this->service->exposure($this->company->id)->invoicesDue);
    }

    public function test_a_settled_invoice_is_never_open(): void
    {
        $paid = $this->invoice(100);
        $this->pay($paid, 120);

        $creditedAway = $this->invoice(100);
        $this->creditNote($creditedAway, 100);

        $open = $this->invoice(100);

        $ids = $this->service->openInvoices($this->company->id)->pluck('id')->all();
        $this->assertSame([$open->id], array_map('intval', $ids));
    }

    public function test_drafts_count_in_the_outstanding_but_are_not_open(): void
    {
        $draft = $this->invoice(100, ['statu' => 1]);

        $this->assertSame([], $this->service->openInvoices($this->company->id)->pluck('id')->all());
        $this->assertSame(120.0, $this->service->exposure($this->company->id)->invoicesDue);
        $this->assertNotNull($draft);
    }

    public function test_proformas_and_deleted_invoices_are_ignored(): void
    {
        $this->invoice(100, ['invoice_type' => 3]);
        $this->invoice(100)->delete();

        $this->assertSame(0.0, $this->service->exposure($this->company->id)->invoicesDue);
    }

    public function test_open_orders_count_what_is_left_to_invoice(): void
    {
        $vat   = AccountingVat::factory()->create(['rate' => 20]);
        $order = Orders::factory()->create(['companies_id' => $this->company->id, 'statu' => 1, 'type' => 1]);
        OrderLines::factory()->create([
            'orders_id' => $order->id, 'line_type' => 'article', 'qty' => 10, 'selling_price' => 50,
            'discount' => 10, 'invoiced_remaining_qty' => 4, 'accounting_vats_id' => $vat->id,
        ]);

        // Ni commande annulée, ni commande interne, ni ligne de présentation.
        $canceled = Orders::factory()->create(['companies_id' => $this->company->id, 'statu' => 6, 'type' => 1]);
        OrderLines::factory()->create(['orders_id' => $canceled->id, 'line_type' => 'article', 'selling_price' => 50, 'invoiced_remaining_qty' => 4]);
        $internal = Orders::factory()->create(['companies_id' => $this->company->id, 'statu' => 1, 'type' => 2]);
        OrderLines::factory()->create(['orders_id' => $internal->id, 'line_type' => 'article', 'selling_price' => 50, 'invoiced_remaining_qty' => 4]);
        OrderLines::factory()->create(['orders_id' => $order->id, 'line_type' => 'section', 'selling_price' => 50, 'invoiced_remaining_qty' => 4]);

        // 4 × 50 × 0,9 × 1,2
        $this->assertSame(216.0, $this->service->exposure($this->company->id)->openOrders);

        config(['receivables.include_open_orders' => false]);
        $this->assertSame(0.0, $this->service->exposure($this->company->id)->openOrders);
    }

    public function test_several_companies_are_computed_in_one_query(): void
    {
        $other = Companies::factory()->create(['statu_customer' => 2, 'credit_limit' => 500, 'credit_block_level' => 'block']);
        $this->invoice(100);
        $this->invoice(200, ['companies_id' => $other->id]);

        DB::enableQueryLog();
        $exposures = $this->service->exposures([$this->company->id, $other->id]);
        $this->assertCount(1, DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(120.0, $exposures[$this->company->id]->total());
        $this->assertNull($exposures[$this->company->id]->limit);
        $this->assertSame(240.0, $exposures[$other->id]->total());
        $this->assertSame(500.0, $exposures[$other->id]->limit);
        $this->assertSame('block', $exposures[$other->id]->level->value);
    }
}
