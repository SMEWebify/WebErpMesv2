<?php

namespace Tests\Feature\Receivables;

use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CreditLimitOverride;
use App\Models\User;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Methods\MethodsUnits;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\PreOrder;
use App\Models\Workflow\PreOrderImport;
use App\Models\Workflow\PreOrderLine;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CreditLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Companies $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        Permission::findOrCreate('credit-limit-override');
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->company = Companies::factory()->create(['statu_customer' => 2]);
        CompaniesContacts::factory()->create(['companies_id' => $this->company->id]);
        CompaniesAddresses::factory()->create(['companies_id' => $this->company->id]);
    }

    /** Société avec 1 200 € TTC de factures dues. */
    private function withOutstanding(?float $limit, ?string $level): void
    {
        $this->company->update(['credit_limit' => $limit, 'credit_block_level' => $level]);

        $invoice = Invoices::factory()->create(['companies_id' => $this->company->id, 'statu' => 2]);
        InvoiceLines::create([
            'invoices_id' => $invoice->id, 'label' => 'Prestation', 'ordre' => 10,
            'qty' => 1, 'unit_price' => 1000, 'discount' => 0, 'vat_rate' => 20,
        ]);
    }

    private function orderPayload(array $extra = []): array
    {
        return array_merge([
            'code'                             => 'OR-CREDIT-' . uniqid(),
            'label'                            => 'Commande',
            'type'                             => 1,
            'companies_id'                     => $this->company->id,
            'companies_contacts_id'            => CompaniesContacts::where('companies_id', $this->company->id)->value('id'),
            'companies_addresses_id'           => CompaniesAddresses::where('companies_id', $this->company->id)->value('id'),
            'accounting_payment_conditions_id' => AccountingPaymentConditions::factory()->create()->id,
            'accounting_payment_methods_id'    => AccountingPaymentMethod::factory()->create()->id,
            'accounting_deliveries_id'         => AccountingDelivery::factory()->create()->id,
            'user_id'                          => $this->user->id,
        ], $extra);
    }

    public function test_a_company_without_limit_is_not_checked(): void
    {
        $this->withOutstanding(null, 'block');

        $this->postJson(route('orders.json.store'), $this->orderPayload())->assertCreated();
        $this->assertNull(session('warning'));
    }

    public function test_warn_level_creates_the_order_with_a_warning(): void
    {
        $this->withOutstanding(1000, 'warn');

        $this->postJson(route('orders.json.store'), $this->orderPayload())->assertCreated();
        $this->assertNotNull(session('warning'));
    }

    public function test_block_level_refuses_the_order(): void
    {
        $this->withOutstanding(1000, 'block');
        $payload = $this->orderPayload();

        $this->postJson(route('orders.json.store'), $payload)
            ->assertStatus(409)
            ->assertJsonPath('credit_limit.level', 'block')
            ->assertJsonPath('credit_limit.outstanding', 1200)
            ->assertJsonPath('credit_limit.can_override', false);

        $this->assertDatabaseMissing('orders', ['code' => $payload['code']]);
    }

    public function test_outstanding_under_the_limit_passes(): void
    {
        $this->withOutstanding(1500, 'block');

        $this->postJson(route('orders.json.store'), $this->orderPayload())->assertCreated();
    }

    public function test_override_requires_the_permission(): void
    {
        $this->withOutstanding(1000, 'block');

        $this->postJson(route('orders.json.store'), $this->orderPayload([
            'credit_override' => 1, 'credit_override_reason' => 'Client historique, règlement annoncé',
        ]))->assertForbidden();

        $this->assertSame(0, CreditLimitOverride::count());
    }

    public function test_override_requires_a_reason(): void
    {
        $this->user->givePermissionTo('credit-limit-override');
        $this->withOutstanding(1000, 'block');

        $this->postJson(route('orders.json.store'), $this->orderPayload(['credit_override' => 1, 'credit_override_reason' => ' ']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('credit_override_reason');
    }

    public function test_override_creates_the_order_and_is_traced(): void
    {
        $this->user->givePermissionTo('credit-limit-override');
        $this->withOutstanding(1000, 'block');

        $response = $this->postJson(route('orders.json.store'), $this->orderPayload([
            'credit_override' => 1, 'credit_override_reason' => 'Règlement annoncé pour vendredi',
        ]))->assertCreated();

        $order    = Orders::latest('id')->first();
        $override = CreditLimitOverride::sole();
        $this->assertSame($order->id, $override->document_id);
        $this->assertSame(Orders::class, $override->document_type);
        $this->assertSame($this->user->id, $override->user_id);
        $this->assertSame('Règlement annoncé pour vendredi', $override->reason);
        $this->assertEquals(1200, $override->outstanding);
        $this->assertEquals(1000, $override->credit_limit);
        $this->assertNotNull($response->json('redirect'));
    }

    public function test_quote_conversion_adds_the_quote_amount(): void
    {
        // 1 200 dus, plafond 1 500 : le devis de 600 TTC fait passer au-delà.
        $this->withOutstanding(1500, 'block');
        $vat   = AccountingVat::factory()->create(['rate' => 20]);
        $quote = Quotes::factory()->create(['statu' => 1, 'user_id' => $this->user->id, 'companies_id' => $this->company->id]);
        $line  = QuoteLines::factory()->create([
            'quotes_id' => $quote->id, 'qty' => 1, 'selling_price' => 500, 'discount' => 0, 'accounting_vats_id' => $vat->id,
        ]);

        $this->postJson(route('quotes.lines.json.store-order', ['quoteId' => $quote->id]), ['line_ids' => [$line->id]])
            ->assertStatus(409)
            ->assertJsonPath('credit_limit.additional', 600)
            ->assertJsonPath('credit_limit.projected', 1800);

        $this->assertSame(0, Orders::where('quotes_id', $quote->id)->count());
    }

    public function test_a_delivery_note_is_blocked_too(): void
    {
        $this->withOutstanding(1000, 'block');
        $order     = Orders::factory()->create(['statu' => 1, 'companies_id' => $this->company->id]);
        $orderLine = OrderLines::factory()->create([
            'orders_id' => $order->id, 'qty' => 10, 'delivered_qty' => 0, 'delivered_remaining_qty' => 10,
            'selling_price' => 0, 'invoiced_remaining_qty' => 0,
        ]);

        $this->postJson(route('deliverys-request.store'), [
            'code'                   => 'BL-CREDIT-001',
            'label'                  => 'BL',
            'companies_id'           => $this->company->id,
            'companies_addresses_id' => $order->companies_addresses_id,
            'companies_contacts_id'  => $order->companies_contacts_id,
            'user_id'                => $this->user->id,
            'lines'                  => [['order_line_id' => $orderLine->id, 'qty' => 5]],
        ])->assertStatus(409);

        $this->assertDatabaseMissing('deliverys', ['code' => 'BL-CREDIT-001']);
    }

    public function test_pre_order_conversion_is_blocked_then_overridden(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\CheckTaskStatus::class);
        $this->withOutstanding(1000, 'block');
        CompaniesContacts::where('companies_id', $this->company->id)->update(['default' => 1]);
        CompaniesAddresses::where('companies_id', $this->company->id)->update(['default' => 1]);
        $import   = PreOrderImport::create(['file_name' => 'batch.csv', 'checksum' => sha1('batch.csv'), 'imported_at' => now()]);
        $preOrder = PreOrder::create(['pre_order_import_id' => $import->id, 'source_pdf' => 'po.pdf', 'status' => PreOrder::STATUS_PENDING]);
        PreOrderLine::create(['pre_order_id' => $preOrder->id, 'row_index' => 1, 'reference' => 'REF', 'quantity' => 2, 'unit_price' => 50]);

        $form = [
            'code' => 'OR-PRE-001', 'label' => 'Pré-commande', 'user_id' => $this->user->id,
            'companies_id' => $this->company->id, 'type' => 1,
            'methods_units_id' => MethodsUnits::factory()->create()->id,
            'accounting_vats_id' => AccountingVat::factory()->create(['rate' => 20])->id,
        ];

        $this->post(route('pre-orders.convert', $preOrder), $form)
            ->assertRedirect(route('pre-orders.show', $preOrder))
            ->assertSessionHasErrors()
            ->assertSessionHas('credit_block');
        $this->assertDatabaseMissing('orders', ['code' => 'OR-PRE-001']);

        $this->user->givePermissionTo('credit-limit-override');
        $this->post(route('pre-orders.convert', $preOrder), $form + ['credit_override' => 1, 'credit_override_reason' => 'Accord du gérant'])
            ->assertSessionHasNoErrors();

        $order = Orders::where('code', 'OR-PRE-001')->sole();
        // 2 × 50 × 1,2 ajoutés aux 1 200 dus.
        $this->assertEquals(1320, CreditLimitOverride::where('document_id', $order->id)->sole()->projected);
    }

    public function test_the_limit_is_only_editable_with_the_permission(): void
    {
        $payload = ['label' => $this->company->label, 'credit_limit' => 5000, 'credit_block_level' => 'block'];

        $this->postJson(route('companies.json.update', $this->company->id), $payload)->assertOk();
        $this->assertNull($this->company->fresh()->credit_limit);

        $this->user->givePermissionTo('credit-limit-override');
        $this->postJson(route('companies.json.update', $this->company->id), $payload)->assertOk();
        $this->assertEquals(5000, $this->company->fresh()->credit_limit);
        $this->assertSame('block', $this->company->fresh()->credit_block_level);
    }

    public function test_companies_list_shows_the_outstanding_of_capped_companies(): void
    {
        $this->withOutstanding(2400, 'warn');

        $row = collect($this->getJson(route('companies.json.list'))->assertOk()->json('data'))
            ->firstWhere('id', $this->company->id);

        $this->assertSame(1200, $row['credit']['total']);
        $this->assertEquals(0.5, $row['credit']['ratio']);
    }
}
