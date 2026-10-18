<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderConfirmationLines;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\DeliveryLineService;
use App\Services\InvoiceLineService;
use App\Services\OrderConfirmationService;
use App\Services\PurchaseOrderService;
use App\Services\QualityNonConformityService;
use App\Services\Quotes\QuoteLineToOrderLineConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'indice du plan (`ind`) suit la pièce du devis à la facture, à la sous-traitance et à la NC.
 * Sans indice saisi, rien ne change : toutes les colonnes restent null.
 */
class PlanIndexChainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function quoteLine(?string $ind): QuoteLines
    {
        $quote = Quotes::factory()->create();
        $vat   = AccountingVat::factory()->create(['rate' => 20]);
        $unit  = MethodsUnits::factory()->create();

        return QuoteLines::create([
            'quotes_id' => $quote->id, 'ordre' => 1, 'code' => 'PIECE-1', 'ind' => $ind,
            'label' => 'Pièce', 'qty' => 5, 'methods_units_id' => $unit->id,
            'selling_price' => 10, 'discount' => 0, 'accounting_vats_id' => $vat->id,
        ]);
    }

    private function orderLineFromQuote(?string $ind): OrderLines
    {
        $quoteLine = $this->quoteLine($ind);
        $order     = Orders::factory()->create();

        return app(QuoteLineToOrderLineConverter::class)->convert($quoteLine, $order->id, 7);
    }

    public function test_the_index_follows_the_line_from_quote_to_order_confirmation(): void
    {
        $orderLine = $this->orderLineFromQuote('B');
        $this->assertSame('B', $orderLine->ind);

        $confirmation = app(OrderConfirmationService::class)->createFromOrder($orderLine->order);
        $arcLine      = OrderConfirmationLines::where('order_confirmation_id', $confirmation->id)->firstOrFail();
        $this->assertSame('B', $arcLine->ind);

        // L'ARC est figé : corriger la commande ensuite ne réécrit pas ce qui a été émis.
        $orderLine->update(['ind' => 'C']);
        $this->assertSame('B', $arcLine->fresh()->ind);
    }

    public function test_the_index_follows_the_line_to_delivery_and_invoice(): void
    {
        $orderLine = $this->orderLineFromQuote('B');
        $delivery  = Deliverys::factory()->create();
        $invoice   = Invoices::factory()->create();

        $deliveryLine = app(DeliveryLineService::class)->createDeliveryLine($delivery, $orderLine->id, 1, 5);
        $this->assertSame('B', $deliveryLine->ind);

        $invoiceLine = app(InvoiceLineService::class)->createInvoiceLine($invoice, $orderLine->id, $deliveryLine->id, 1, 5, $orderLine->accounting_vats_id);
        $this->assertSame('B', $invoiceLine->ind);
        $this->assertSame('B', $invoiceLine->display_ind);

        // Correction ultérieure de la commande : BL et facture gardent l'indice livré / facturé.
        $orderLine->update(['ind' => 'C']);
        $this->assertSame('B', $deliveryLine->fresh()->ind);
        $this->assertSame('B', $invoiceLine->fresh()->display_ind);
    }

    public function test_an_old_invoice_line_without_snapshot_falls_back_on_the_order_line(): void
    {
        $orderLine   = $this->orderLineFromQuote('A');
        $invoiceLine = new InvoiceLines(['order_line_id' => $orderLine->id]);
        $invoiceLine->setRelation('orderLine', $orderLine);

        $this->assertNull($invoiceLine->ind);
        $this->assertSame('A', $invoiceLine->display_ind);
    }

    public function test_without_an_index_nothing_is_set_anywhere(): void
    {
        $orderLine = $this->orderLineFromQuote(null);
        $delivery  = Deliverys::factory()->create();
        $invoice   = Invoices::factory()->create();

        $deliveryLine = app(DeliveryLineService::class)->createDeliveryLine($delivery, $orderLine->id, 1, 5);
        $invoiceLine  = app(InvoiceLineService::class)->createInvoiceLine($invoice, $orderLine->id, $deliveryLine->id, 1, 5, $orderLine->accounting_vats_id);
        $confirmation = app(OrderConfirmationService::class)->createFromOrder($orderLine->order);

        $this->assertNull($orderLine->ind);
        $this->assertNull($deliveryLine->ind);
        $this->assertNull($invoiceLine->display_ind);
        $this->assertNull(OrderConfirmationLines::where('order_confirmation_id', $confirmation->id)->value('ind'));
    }

    public function test_duplicating_a_line_keeps_its_index(): void
    {
        $line = $this->quoteLine('B');
        $copy = $line->replicate();
        $copy->save();

        $this->assertSame('B', $copy->fresh()->ind);
    }

    public function test_subcontracting_purchase_line_gets_the_index_of_the_order_line(): void
    {
        $orderLine = $this->orderLineFromQuote('B');

        $task = new Task(['code' => 'ST-1', 'label' => 'Traitement', 'order_lines_id' => $orderLine->id, 'qty' => 5, 'unit_cost' => 3]);
        $task->id = 999;
        $task->setRelation('OrderLines', $orderLine);

        $purchase = \App\Models\Purchases\Purchases::factory()->create();
        $line     = app(PurchaseOrderService::class)->createPurchaseOrderLine($purchase->id, $task, $orderLine->accounting_vats_id, 1, 3);

        $this->assertSame('B', $line->ind);
    }

    public function test_non_conformity_takes_the_index_of_the_order_line(): void
    {
        $orderLine = $this->orderLineFromQuote('B');
        $company   = Companies::factory()->create();

        $nc = app(QualityNonConformityService::class)->createNC($orderLine->id, $company->id);

        $this->assertSame('B', $nc->ind);
    }

    public function test_the_api_accepts_an_optional_index_per_line(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs(User::factory()->create());
        $company = Companies::factory()->create();
        $contact = \App\Models\Companies\CompaniesContacts::factory()->create(['companies_id' => $company->id]);
        $address = \App\Models\Companies\CompaniesAddresses::factory()->create(['companies_id' => $company->id]);

        $payload = fn (array $line) => [
            'label' => 'Devis indice', 'companies_id' => $company->id,
            'companies_contacts_id' => $contact->id, 'companies_addresses_id' => $address->id,
            'accounting_payment_conditions_id' => \App\Models\Accounting\AccountingPaymentConditions::value('id'),
            'accounting_payment_methods_id'    => \App\Models\Accounting\AccountingPaymentMethod::value('id'),
            'accounting_deliveries_id'         => \App\Models\Accounting\AccountingDelivery::value('id'),
            'lines' => [$line + [
                'ordre' => 1, 'label' => 'P', 'qty' => 1, 'selling_price' => 1,
                'accounting_vats_id' => AccountingVat::value('id'), 'methods_units_id' => MethodsUnits::value('id'),
            ]],
        ];

        $with = $this->postJson('/api/quote', $payload(['ind' => 'B']));
        $with->assertCreated();
        $this->assertSame('B', Quotes::findOrFail($with->json('data.id'))->QuoteLines()->first()->ind);

        $without = $this->postJson('/api/quote', $payload([]));
        $without->assertCreated();
        $this->assertNull(Quotes::findOrFail($without->json('data.id'))->QuoteLines()->first()->ind);

        $this->postJson('/api/quote', $payload(['ind' => str_repeat('X', 51)]))->assertStatus(422);
    }

    public function test_the_product_picker_returns_the_article_index(): void
    {
        MethodsUnits::factory()->create();
        \App\Models\Methods\MethodsFamilies::factory()->create();
        Products::factory()->create(['code' => 'ART-IND', 'ind' => 'D']);

        $found = app(\App\Services\SelectDataService::class)->searchProducts('ART-IND')->first();

        $this->assertSame('D', $found->ind);
    }
}
