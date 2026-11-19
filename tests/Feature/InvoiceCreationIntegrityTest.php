<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\User;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Création de facture et de BL : une ligne n'est jamais facturée ni livrée deux fois
 * (double clic, rechargement, second utilisateur), et un document sans ligne n'est pas
 * créé, donc ne consomme aucun numéro.
 */
class InvoiceCreationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Companies $customer;
    private AccountingVat $vat;

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

        $this->customer = Companies::factory()->create(['statu_customer' => 2]);
        CompaniesContacts::factory()->create(['companies_id' => $this->customer->id]);
        CompaniesAddresses::factory()->create(['companies_id' => $this->customer->id]);
        $this->vat = AccountingVat::factory()->create(['rate' => 20]);
    }

    private function order(): Orders
    {
        return Orders::factory()->create(['companies_id' => $this->customer->id, 'type' => 1, 'statu' => 1]);
    }

    private function line(Orders $order, array $attributes = []): OrderLines
    {
        return OrderLines::factory()->create($attributes + [
            'orders_id' => $order->id, 'qty' => 4,
            'delivered_qty' => 0, 'delivered_remaining_qty' => 4, 'invoiced_qty' => 0, 'invoiced_remaining_qty' => 4,
            'delivery_status' => 1, 'invoice_status' => 1, 'line_type' => 'article', 'selling_price' => 100,
            'discount' => 0, 'accounting_vats_id' => $this->vat->id,
        ]);
    }

    /** Ligne déjà livrée en entier, avec sa ligne de BL à facturer. */
    private function deliveredLine(): DeliveryLines
    {
        $order = $this->order();
        $line  = $this->line($order, ['delivered_qty' => 4, 'delivered_remaining_qty' => 0, 'delivery_status' => 3]);
        $delivery = Deliverys::factory()->create([
            'companies_id' => $order->companies_id, 'companies_contacts_id' => $order->companies_contacts_id,
            'companies_addresses_id' => $order->companies_addresses_id, 'order_id' => $order->id,
        ]);

        return DeliveryLines::create([
            'deliverys_id' => $delivery->id, 'order_line_id' => $line->id, 'ordre' => 10, 'qty' => 4, 'invoice_status' => 1,
        ]);
    }

    private function counterValue(string $type): ?int
    {
        $value = DB::table('document_code_counters')->where('document_type', $type)->max('current_value');

        return $value === null ? null : (int) $value;
    }

    public function test_delivery_lines_sent_twice_are_invoiced_once(): void
    {
        $deliveryLine = $this->deliveredLine();
        $order = $deliveryLine->OrderLine->order;
        $payload = fn (string $code) => [
            'code' => $code, 'label' => 'Facture', 'companies_id' => $order->companies_id,
            'companies_addresses_id' => $order->companies_addresses_id, 'companies_contacts_id' => $order->companies_contacts_id,
            'user_id' => $this->user->id, 'lines' => [$deliveryLine->id],
        ];

        $this->postJson(route('invoices-request.store'), $payload('FA-1'))->assertOk();
        $this->postJson(route('invoices-request.store'), $payload('FA-2'))->assertStatus(422)->assertJsonValidationErrors('lines');

        $this->assertSame(1, InvoiceLines::where('delivery_line_id', $deliveryLine->id)->count());
        $this->assertDatabaseMissing('invoices', ['code' => 'FA-2']);
        $this->assertEquals(4, (float) $deliveryLine->OrderLine->fresh()->invoiced_qty);
    }

    public function test_invoicing_a_delivery_twice_creates_one_invoice(): void
    {
        $deliveryLine = $this->deliveredLine();

        $this->get(route('invoices.store.from.delivery', $deliveryLine->deliverys_id))->assertRedirect();
        $counter = $this->counterValue('invoice');

        // Rechargement de la page : rien n'est plus à facturer, aucun numéro n'est pris.
        $this->get(route('invoices.store.from.delivery', $deliveryLine->deliverys_id))
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame(1, Invoices::count());
        $this->assertSame(1, InvoiceLines::where('delivery_line_id', $deliveryLine->id)->count());
        $this->assertSame($counter, $this->counterValue('invoice'));
    }

    public function test_direct_invoicing_of_an_invoiced_line_creates_nothing(): void
    {
        $order = $this->order();
        $line  = $this->line($order, [
            'delivered_qty' => 4, 'delivered_remaining_qty' => 0, 'invoiced_qty' => 4, 'invoiced_remaining_qty' => 0,
        ]);

        $this->postJson(route('orders.lines.json.store-invoice', $order->id), ['line_ids' => [$line->id]])
            ->assertStatus(422);

        // Ni facture ni BL créés puis supprimés : aucun trou dans la numérotation.
        $this->assertSame(0, Invoices::withTrashed()->count());
        $this->assertSame(0, Deliverys::count());
        $this->assertNull($this->counterValue('invoice'));
        $this->assertNull($this->counterValue('delivery'));
    }

    public function test_direct_invoicing_of_a_delivered_line_creates_no_delivery_note(): void
    {
        $order = $this->order();
        $line  = $this->line($order, ['delivered_qty' => 4, 'delivered_remaining_qty' => 0, 'delivery_status' => 3]);

        $this->postJson(route('orders.lines.json.store-invoice', $order->id), ['line_ids' => [$line->id]])->assertOk();
        $this->postJson(route('orders.lines.json.store-invoice', $order->id), ['line_ids' => [$line->id]])->assertStatus(422);

        $this->assertSame(1, InvoiceLines::where('order_line_id', $line->id)->count());
        $this->assertSame(0, Deliverys::count());
        $this->assertNull($this->counterValue('delivery'));
        $this->assertEquals(4, (float) $line->fresh()->invoiced_qty);
    }

    public function test_direct_invoicing_delivers_and_invoices_each_selected_line_once(): void
    {
        $order = $this->order();
        $line  = $this->line($order);

        // La même ligne cochée deux fois n'est comptée qu'une fois.
        $this->postJson(route('orders.lines.json.store-invoice', $order->id), ['line_ids' => [$line->id, $line->id]])->assertOk();

        $this->assertSame(1, InvoiceLines::where('order_line_id', $line->id)->count());
        $this->assertSame(1, DeliveryLines::where('order_line_id', $line->id)->count());
        $line->refresh();
        $this->assertEquals(4, (float) $line->delivered_qty);
        $this->assertEquals(4, (float) $line->invoiced_qty);
        $this->assertEquals(0, (float) $line->invoiced_remaining_qty);
    }

    public function test_a_delivery_note_requested_twice_is_created_once(): void
    {
        $order = $this->order();
        $line  = $this->line($order);

        $this->postJson(route('orders.lines.json.store-delivery', $order->id), ['line_ids' => [$line->id]])->assertOk();
        $counter = $this->counterValue('delivery');
        $this->postJson(route('orders.lines.json.store-delivery', $order->id), ['line_ids' => [$line->id]])->assertStatus(422);

        $this->assertSame(1, Deliverys::count());
        $this->assertSame(1, DeliveryLines::where('order_line_id', $line->id)->count());
        $this->assertSame($counter, $this->counterValue('delivery'));
        $this->assertEquals(4, (float) $line->fresh()->delivered_qty);
    }
}
