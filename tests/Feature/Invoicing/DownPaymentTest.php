<?php

namespace Tests\Feature\Invoicing;

use App\Models\Accounting\AccountingAllocation;
use App\Models\Accounting\AccountingEntry;
use App\Models\Accounting\AccountingVat;
use App\Models\User;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\InvoicePayment;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\InvoiceCalculatorService;
use App\Services\Invoicing\DownPayments;
use App\Services\Receivables\ReceivablesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Commande de 10 pièces à 100 € HT (TVA 20 %), acompte de 50 % payé, livrée en deux fois.
 */
class DownPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Orders $order;
    private OrderLines $line;

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

        $this->order = Orders::factory()->create(['statu' => 1, 'type' => 1]);
        $this->line  = OrderLines::factory()->create([
            'orders_id' => $this->order->id, 'qty' => 10, 'selling_price' => 100, 'discount' => 0,
            'delivered_qty' => 0, 'delivered_remaining_qty' => 10,
            'invoiced_qty' => 0, 'invoiced_remaining_qty' => 10,
            'accounting_vats_id' => $vat->id, 'use_calculated_price' => 0,
        ]);
    }

    public function test_a_down_payment_invoice_does_not_touch_quantities(): void
    {
        $acompte = $this->createDownPayment(50);

        $this->assertSame(DownPayments::TYPE, (int) $acompte->invoice_type);
        $this->assertSame($this->order->id, (int) $acompte->order_id);
        $this->assertEqualsWithDelta(500.0, (new InvoiceCalculatorService($acompte))->getSubTotal(), 0.001);
        $this->assertEqualsWithDelta(600.0, (new InvoiceCalculatorService($acompte))->getTotalPrice(), 0.001);
        $this->assertNull($acompte->invoiceLines->first()->order_line_id);

        $this->line->refresh();
        $this->assertEquals(0, (float) $this->line->invoiced_qty);
        $this->assertEquals(10, (float) $this->line->invoiced_remaining_qty);
    }

    public function test_the_down_payment_page_titles_it_without_double_escaping(): void
    {
        $acompte = $this->createDownPayment(50);

        $this->get(route('invoices.show', ['id' => $acompte->id]))
            ->assertOk()
            ->assertSee(__('down_payments.document_title') . ' : ' . $acompte->code)
            ->assertDontSee('&amp;#039;', false)
            // Fil d'Ariane : remonte à la commande par order_id (aucune ligne de commande).
            ->assertSee(route('orders.show', ['id' => $this->order->id]), false);

        // Et la commande liste son acompte.
        $this->get(route('orders.show', ['id' => $this->order->id]))
            ->assertOk()
            ->assertSee(route('invoices.show', ['id' => $acompte->id]), false);
    }

    public function test_the_down_payment_is_booked_on_the_advance_account(): void
    {
        $acompte = $this->createDownPayment(50);
        $entries = AccountingEntry::whereIn('invoice_line_id', $acompte->invoiceLines->pluck('id'))->get();

        $this->assertEquals(600.0, (float) $entries->where('account_number', '411')->sum('debit_amount'));
        $this->assertEquals(500.0, (float) $entries->where('account_number', '4191')->sum('credit_amount'));
        $this->assertEquals(100.0, (float) $entries->where('account_number', '445710')->sum('credit_amount'));
        $this->assertSame(0, $entries->where('account_number', '701000')->count());
    }

    public function test_a_paid_down_payment_is_deducted_in_proportion_to_each_delivery(): void
    {
        $acompte = $this->createDownPayment(50);
        $this->issueAndPay($acompte, 600);

        // Livraison de 5 pièces : la moitié de la commande, la moitié de l'acompte.
        $first = $this->invoiceDelivery(5);
        $this->assertDeduction($first, 250.0);
        $this->assertEqualsWithDelta(300.0, (new InvoiceCalculatorService($first))->getTotalPrice(), 0.001);
        $this->assertEquals(5, (float) $this->line->fresh()->invoiced_qty);

        // Les 5 dernières : la facture qui solde la commande prend le reste.
        $second = $this->invoiceDelivery(5);
        $this->assertDeduction($second, 250.0);
        $this->assertEqualsWithDelta(300.0, (new InvoiceCalculatorService($second))->getTotalPrice(), 0.001);
        $this->assertEquals(0, (float) $this->line->fresh()->invoiced_remaining_qty);

        $this->assertSame(0.0, app(DownPayments::class)->summary($acompte->fresh('invoiceLines'))['remaining']);
    }

    public function test_the_order_balance_counts_the_down_payment_once(): void
    {
        $service = app(\App\Services\OrderInvoiceDataService::class);
        $money   = fn (float $v) => \Illuminate\Support\Number::currency($v, 'EUR', config('app.locale'));

        $acompte = $this->createDownPayment(50);
        $this->issueAndPay($acompte, 600);
        $this->assertEqualsWithDelta(600.0, $service->getInvoicingAmount($this->order->fresh()), 0.01);
        $this->assertSame($money(600), $service->getInvoicingReceivedPayment($this->order->fresh()));

        // 5 pièces : facture nette de 300 TTC. Facturé = 600 + 300, pas 600 + 600.
        $this->invoiceDelivery(5);
        $this->assertEqualsWithDelta(900.0, $service->getInvoicingAmount($this->order->fresh()), 0.01);
        $this->assertSame($money(600), $service->getInvoicingReceivedPayment($this->order->fresh()));
    }

    public function test_the_deduction_reverses_the_down_payment_entry(): void
    {
        $acompte = $this->createDownPayment(50);
        $this->issueAndPay($acompte, 600);
        $invoice = $this->invoiceDelivery(5);

        $deduction = InvoiceLines::where('invoices_id', $invoice->id)->whereNotNull('down_payment_line_id')->firstOrFail();
        $entries   = AccountingEntry::where('invoice_line_id', $deduction->id)->get();

        $this->assertEquals(300.0, (float) $entries->where('account_number', '411')->sum('credit_amount'));
        $this->assertEquals(250.0, (float) $entries->where('account_number', '4191')->sum('debit_amount'));
        $this->assertEquals(50.0, (float) $entries->where('account_number', '445710')->sum('debit_amount'));
        $this->assertEquals((float) $entries->sum('debit_amount'), (float) $entries->sum('credit_amount'));
    }

    public function test_an_unpaid_down_payment_is_not_deducted_then_caught_up(): void
    {
        $acompte = $this->createDownPayment(50);
        $this->issueAndPay($acompte, 0);

        $first = $this->invoiceDelivery(5);
        $this->assertDeduction($first, 0.0);

        // Payé ensuite : la facture suivante rattrape toute la part due.
        $this->pay($acompte, 600);
        $second = $this->invoiceDelivery(5);
        $this->assertDeduction($second, 500.0);
    }

    public function test_a_draft_down_payment_is_not_deducted(): void
    {
        $this->createDownPayment(50);

        $this->assertDeduction($this->invoiceDelivery(5), 0.0);
    }

    public function test_direct_invoicing_deducts_the_whole_down_payment(): void
    {
        $acompte = $this->createDownPayment(30);
        $this->issueAndPay($acompte, 360);

        $this->postJson(route('orders.lines.json.store-invoice', ['orderId' => $this->order->id]), [
            'line_ids' => [$this->line->id],
        ])->assertOk();

        $invoice = Invoices::where('invoice_type', 1)->latest('id')->firstOrFail();
        $this->assertDeduction($invoice, 300.0);
    }

    public function test_a_deduction_can_be_lowered_but_not_raised_beyond_the_paid_part(): void
    {
        $acompte = $this->createDownPayment(50);
        $this->issueAndPay($acompte, 600);
        $invoice   = $this->invoiceDelivery(5);
        $deduction = InvoiceLines::where('invoices_id', $invoice->id)->whereNotNull('down_payment_line_id')->firstOrFail();
        $url       = route('invoices.lines.update', [$invoice->id, $deduction->id]);

        $this->patchJson($url, ['unit_price' => 100])->assertOk();
        $this->assertEquals(100.0, (float) $deduction->fresh()->unit_price);
        $this->assertEquals(-1.0, (float) $deduction->fresh()->qty);

        $this->patchJson($url, ['unit_price' => 600])->assertStatus(422);
        $this->patchJson($url, ['qty' => 3])->assertStatus(422);
        $this->assertEquals(100.0, (float) $deduction->fresh()->unit_price);

        // Recalcul : retour au prorata.
        $this->post(route('invoices.down-payments.recompute', $invoice->id))->assertRedirect();
        $this->assertDeduction($invoice->fresh(), 250.0);
    }

    public function test_down_payments_cannot_exceed_the_order_total(): void
    {
        $this->createDownPayment(80);

        $this->post(route('invoices.down-payments.store', $this->order->id), ['mode' => 'amount', 'value' => 300])
            ->assertSessionHas('error');
        $this->assertSame(1, Invoices::where('invoice_type', DownPayments::TYPE)->count());
    }

    public function test_the_down_payment_is_a_receivable_and_not_double_counted_in_exposure(): void
    {
        $acompte = $this->createDownPayment(50);
        $this->issueAndPay($acompte, 0);
        $receivables = app(ReceivablesService::class);

        $this->assertEqualsWithDelta(600.0, (float) $receivables->invoiceBalance($acompte->id)->balance, 0.001);

        // 600 dus sur l'acompte + 1 200 commandés − 600 déjà couverts par l'acompte.
        $exposure = $receivables->exposure($this->order->companies_id);
        $this->assertEqualsWithDelta(600.0, $exposure->invoicesDue, 0.01);
        $this->assertEqualsWithDelta(600.0, $exposure->openOrders, 0.01);
    }

    public function test_a_deduction_cannot_be_credited_but_the_down_payment_can_on_the_advance_account(): void
    {
        $acompte = $this->createDownPayment(50);
        $this->issueAndPay($acompte, 600);
        $invoice = $this->invoiceDelivery(5);
        $this->patchJson(route('invoices.emit', $invoice->id))->assertOk();
        $deduction = InvoiceLines::where('invoices_id', $invoice->id)->whereNotNull('down_payment_line_id')->firstOrFail();

        $this->post(route('credit-notes.store.from.invoice'), ['selected_invoice_lines' => [$deduction->id]])
            ->assertSessionHas('error', __('down_payments.refused.credit_note_on_deduction'));

        $acompteLine = $acompte->invoiceLines->first();
        $this->post(route('credit-notes.store.from.invoice'), ['selected_invoice_lines' => [$acompteLine->id]])
            ->assertRedirect();
        $creditLineIds = \App\Models\Workflow\CreditNoteLines::where('invoice_line_id', $acompteLine->id)->pluck('id');
        $this->assertEquals(500.0, (float) AccountingEntry::whereIn('credit_note_line_id', $creditLineIds)->where('account_number', '4191')->sum('debit_amount'));
    }

    public function test_facturx_types_the_down_payment_and_references_it_on_the_final_invoice(): void
    {
        $this->makeIdentifiableParties();
        $acompte = $this->createDownPayment(50);
        $this->issueAndPay($acompte, 600);
        $invoice = $this->invoiceDelivery(10);
        $this->patchJson(route('invoices.emit', $invoice->id))->assertOk();

        $builder = app(\App\Services\Invoicing\FacturXBuilder::class);

        $acompteXml = $builder->buildXml($acompte->fresh());
        $this->assertStringContainsString('<ram:TypeCode>386</ram:TypeCode>', $acompteXml);

        $finalXml = $builder->buildXml($invoice->fresh());
        $this->assertStringContainsString('<ram:TypeCode>380</ram:TypeCode>', $finalXml);
        $this->assertMatchesRegularExpression('/<ram:InvoiceReferencedDocument>\s*<ram:IssuerAssignedID>' . preg_quote($acompte->code, '/') . '</', $finalXml);
        $this->assertStringContainsString('<ram:ID>M4</ram:ID>', $finalXml);
        $this->assertStringContainsString('<ram:BilledQuantity unitCode="C62">-1', $finalXml);
        // 1 000 − 500 HT, + TVA 20 % = 600 TTC.
        $this->assertStringContainsString('<ram:GrandTotalAmount>600.00</ram:GrandTotalAmount>', $finalXml);
    }

    /** Vendeur et client identifiables : FacturXBuilder refuse sinon de construire le document. */
    private function makeIdentifiableParties(): void
    {
        $factory = \App\Models\Admin\Factory::first() ?? new \App\Models\Admin\Factory();
        $factory->fill([
            'name' => 'Burger Queen', 'siren' => '000000002', 'vat_num' => 'FR18000000002',
            'address' => '809 avenue du Languedoc', 'zipcode' => '12100', 'city' => 'Millau',
            'country' => 'FR', 'curency' => 'EUR',
        ])->save();
        $this->app->forgetInstance('Factory');

        $this->order->companie->update(['siren' => '000000001', 'intra_community_vat' => 'FR15000000001']);
        $this->order->adresse->update(['adress' => '12 rue de la Gastronomie', 'zipcode' => '12100', 'city' => 'Millau', 'country' => 'FR']);
    }

    private function createDownPayment(float $percent): Invoices
    {
        $this->post(route('invoices.down-payments.store', $this->order->id), ['mode' => 'percent', 'value' => $percent])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        return Invoices::with('invoiceLines')->where('invoice_type', DownPayments::TYPE)->latest('id')->firstOrFail();
    }

    private function issueAndPay(Invoices $invoice, float $amount): void
    {
        $this->patchJson(route('invoices.emit', $invoice->id))->assertOk();
        $this->pay($invoice, $amount);
    }

    private function pay(Invoices $invoice, float $amount): void
    {
        if ($amount > 0) {
            InvoicePayment::create(['invoice_id' => $invoice->id, 'amount' => $amount, 'payment_date' => now(), 'user_id' => $this->user->id]);
        }
    }

    private function invoiceDelivery(float $qty): Invoices
    {
        $delivery = Deliverys::factory()->create([
            'companies_id' => $this->order->companies_id, 'companies_contacts_id' => $this->order->companies_contacts_id,
            'companies_addresses_id' => $this->order->companies_addresses_id, 'order_id' => $this->order->id,
        ]);
        $deliveryLine = DeliveryLines::create([
            'deliverys_id' => $delivery->id, 'order_line_id' => $this->line->id,
            'ordre' => 10, 'qty' => $qty, 'invoice_status' => 1,
        ]);

        $this->postJson(route('invoices-request.store'), [
            'code' => 'FA-DP-' . $deliveryLine->id, 'label' => 'Test', 'companies_id' => $this->order->companies_id,
            'companies_addresses_id' => $this->order->companies_addresses_id,
            'companies_contacts_id' => $this->order->companies_contacts_id,
            'user_id' => $this->user->id, 'lines' => [$deliveryLine->id],
        ])->assertOk();

        return Invoices::where('code', 'FA-DP-' . $deliveryLine->id)->firstOrFail();
    }

    private function assertDeduction(Invoices $invoice, float $ht): void
    {
        $lines = InvoiceLines::where('invoices_id', $invoice->id)->whereNotNull('down_payment_line_id')->get();

        $this->assertEqualsWithDelta($ht, $lines->sum(fn ($l) => -(float) $l->qty * (float) $l->unit_price), 0.001);
    }
}
