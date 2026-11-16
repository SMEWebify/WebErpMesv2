<?php

namespace Tests\Feature\Invoicing;

use App\Models\Accounting\AccountingAllocation;
use App\Models\Accounting\AccountingEntry;
use App\Models\Accounting\AccountingVat;
use App\Models\User;
use App\Models\Workflow\CreditNoteLines;
use App\Models\Workflow\CreditNotes;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Receivables\ReceivablesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Facture de 10 pièces à 100 € HT (TVA 20 %) : avoirs partiels en quantité et en prix.
 */
class PartialCreditNoteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private OrderLines $line;
    private Invoices $invoice;
    private InvoiceLines $invoiceLine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $vat = AccountingVat::factory()->create(['rate' => 20]);
        AccountingAllocation::create([
            'account' => '701000', 'label' => 'Ventes', 'accounting_vats_id' => $vat->id,
            'vat_account' => '445710', 'code_account' => '701000', 'type_imputation' => 1,
        ]);

        $order      = Orders::factory()->create(['statu' => 1, 'type' => 1]);
        $this->line = OrderLines::factory()->create([
            'orders_id' => $order->id, 'qty' => 10, 'selling_price' => 100, 'discount' => 0,
            'delivered_qty' => 10, 'delivered_remaining_qty' => 0,
            'invoiced_qty' => 0, 'invoiced_remaining_qty' => 10,
            'accounting_vats_id' => $vat->id, 'use_calculated_price' => 0,
        ]);

        $delivery = Deliverys::factory()->create([
            'companies_id' => $order->companies_id, 'companies_contacts_id' => $order->companies_contacts_id,
            'companies_addresses_id' => $order->companies_addresses_id, 'order_id' => $order->id,
        ]);
        $deliveryLine = DeliveryLines::create([
            'deliverys_id' => $delivery->id, 'order_line_id' => $this->line->id, 'ordre' => 10, 'qty' => 10, 'invoice_status' => 1,
        ]);
        $this->postJson(route('invoices-request.store'), [
            'code' => 'FA-CN-1', 'label' => 'Test', 'companies_id' => $order->companies_id,
            'companies_addresses_id' => $order->companies_addresses_id,
            'companies_contacts_id' => $order->companies_contacts_id,
            'user_id' => $this->user->id, 'lines' => [$deliveryLine->id],
        ])->assertOk();

        $this->invoice     = Invoices::where('code', 'FA-CN-1')->firstOrFail();
        $this->patchJson(route('invoices.emit', $this->invoice->id))->assertOk();
        $this->invoiceLine = $this->invoice->invoiceLines()->firstOrFail();
    }

    public function test_two_scrapped_parts_are_credited_and_go_back_to_invoice(): void
    {
        $this->credit(qty: 2)->assertRedirect()->assertSessionHasNoErrors();

        $creditLine = CreditNoteLines::firstOrFail();
        $this->assertEquals(2, (float) $creditLine->qty);
        $this->assertEquals(100, (float) $creditLine->unit_price);
        $this->assertTrue($creditLine->restores_quantity);

        $this->line->refresh();
        $this->assertEquals(8, (float) $this->line->invoiced_qty);
        $this->assertEquals(2, (float) $this->line->invoiced_remaining_qty);

        // 2 × 100 HT + TVA = 240 TTC déduits du reste dû (1 200 − 240).
        $this->assertEqualsWithDelta(960.0, (float) app(ReceivablesService::class)->invoiceBalance($this->invoice->id)->balance, 0.01);
        $this->assertEquals(240.0, (float) AccountingEntry::where('credit_note_line_id', $creditLine->id)->where('account_number', '411')->sum('credit_amount'));
    }

    public function test_a_commercial_gesture_credits_an_amount_without_touching_quantities(): void
    {
        // Remise de 10 € par pièce sur les 10, pièces gardées : rien à refacturer.
        $this->credit(qty: 10, price: 10, restore: false)->assertRedirect()->assertSessionHasNoErrors();

        $creditLine = CreditNoteLines::firstOrFail();
        $this->assertFalse($creditLine->restores_quantity);
        $this->assertEquals(10, (float) $this->line->fresh()->invoiced_qty);
        $this->assertEquals(0, (float) $this->line->fresh()->invoiced_remaining_qty);
        $this->assertEqualsWithDelta(1080.0, (float) app(ReceivablesService::class)->invoiceBalance($this->invoice->id)->balance, 0.01);
    }

    public function test_credit_notes_never_exceed_what_the_line_carries(): void
    {
        $this->credit(qty: 8)->assertSessionHasNoErrors();

        // 2 pièces restent créditables : 3 est refusé, rien n'est créé.
        $this->credit(qty: 3)->assertSessionHas('error');
        $this->assertSame(1, CreditNotes::count());

        // Un prix supérieur au prix facturé aussi.
        $this->credit(qty: 1, price: 150)->assertSessionHas('error');

        // Le reste passe, puis la ligne est entièrement en avoir.
        $this->credit(qty: 2)->assertSessionHasNoErrors();
        $this->credit()->assertSessionHas('error', __('credit_notes.refused.fully_credited', ['line' => $this->invoiceLine->display_code ?: $this->invoiceLine->display_label]));
        $this->assertSame(2, CreditNotes::count());
    }

    public function test_the_former_form_still_credits_the_whole_line(): void
    {
        $this->post(route('credit-notes.store.from.invoice'), ['selected_invoice_lines' => [$this->invoiceLine->id]])
            ->assertRedirect();

        $this->assertEquals(10, (float) CreditNoteLines::firstOrFail()->qty);
        $this->assertEquals(0, (float) $this->line->fresh()->invoiced_qty);
        $this->assertEquals(10, (float) $this->line->fresh()->invoiced_remaining_qty);
    }

    public function test_a_rejected_credit_note_takes_its_quantities_back_and_frees_the_line(): void
    {
        $this->credit(qty: 2);
        $creditNote = CreditNotes::firstOrFail();

        $this->setStatus($creditNote, 3)->assertSessionHasNoErrors();
        $this->assertEquals(10, (float) $this->line->fresh()->invoiced_qty);
        $this->assertEquals(0, (float) $this->line->fresh()->invoiced_remaining_qty);

        // Refusé : la ligne redevient entièrement créditable.
        $this->credit(qty: 10)->assertSessionHasNoErrors();
    }

    public function test_a_credit_note_cannot_be_rejected_once_its_quantities_were_invoiced_again(): void
    {
        $this->credit(qty: 2);
        $creditNote = CreditNotes::firstOrFail();

        // Les 2 pièces refaites sont refacturées.
        $this->line->update(['invoiced_qty' => 10, 'invoiced_remaining_qty' => 0]);

        $this->setStatus($creditNote, 3)->assertSessionHas('error');
        $this->assertSame(1, (int) $creditNote->fresh()->statu);
    }

    public function test_the_invoice_page_shows_the_partial_credit_form(): void
    {
        $this->credit(qty: 4);

        $this->get(route('invoices.show', ['id' => $this->invoice->id]))
            ->assertOk()
            ->assertSee(__('credit_notes.form.title'))
            ->assertSee('name="qty[' . $this->invoiceLine->id . ']"', false)
            ->assertSee('max="6"', false);
    }

    private function credit(?float $qty = null, ?float $price = null, bool $restore = true)
    {
        $payload = ['selected_invoice_lines' => [$this->invoiceLine->id], 'restore_quantities_sent' => 1];
        if ($restore) {
            $payload['restore_quantities'] = 1;
        }
        if ($qty !== null) {
            $payload['qty'] = [$this->invoiceLine->id => $qty];
        }
        if ($price !== null) {
            $payload['unit_price'] = [$this->invoiceLine->id => $price];
        }

        return $this->from(route('invoices.show', ['id' => $this->invoice->id]))
            ->post(route('credit-notes.store.from.invoice'), $payload);
    }

    private function setStatus(CreditNotes $creditNote, int $statu)
    {
        return $this->from(route('credit.notes.show', ['id' => $creditNote->id]))
            ->post(route('credit.notes.update', ['id' => $creditNote->id]), [
                'id' => $creditNote->id, 'label' => $creditNote->label, 'statu' => $statu,
            ]);
    }
}
