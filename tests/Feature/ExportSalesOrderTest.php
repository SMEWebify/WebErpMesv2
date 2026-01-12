<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Methods\MethodsUnits;
use App\Models\User;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Task;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExportSalesOrderTest extends TestCase
{
    use RefreshDatabase;

    private MethodsUnits $unit;
    private AccountingVat $vat;
    private AccountingPaymentConditions $paymentCondition;
    private AccountingPaymentMethod $paymentMethod;
    private AccountingDelivery $delivery;
    private User $user;
    private Companies $company;
    private CompaniesContacts $contact;
    private CompaniesAddresses $address;
    private MethodsServices $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->unit = MethodsUnits::create([
            'code' => 'UNIT',
            'label' => 'Unit',
            'type' => 5,
            'default' => 0,
        ]);
        $this->vat = AccountingVat::factory()->create(['rate' => 20]);
        $this->paymentCondition = AccountingPaymentConditions::factory()->create();
        $this->paymentMethod = AccountingPaymentMethod::factory()->create();
        $this->delivery = AccountingDelivery::factory()->create();
        $this->company = Companies::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $this->contact = CompaniesContacts::factory()->create([
            'companies_id' => $this->company->id,
        ]);
        $this->address = CompaniesAddresses::factory()->create([
            'companies_id' => $this->company->id,
        ]);

        $this->service = MethodsServices::create([
            'code' => 'SRV-001',
            'ordre' => 1,
            'label' => 'Usinage',
            'type' => 1,
            'hourly_rate' => 60,
            'margin' => 10,
            'color' => '#FFFFFF',
            'companies_id' => $this->company->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_can_retrieve_sales_orders_as_json(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 10, 8, 0, 0));

        $this->createOrderWithLines([
            'code' => 'SO-0001',
            'label' => 'Order 1',
            'customer_reference' => 'REF-001',
            'validity_date' => Carbon::create(2024, 1, 15),
        ]);

        $this->actingAs($this->user, 'api');
        $response = $this->getJson('/api/exports/sales-orders');

        $response->assertOk();
        $response->assertJson(['meta' => ['count' => 1]]);
        $response->assertJsonCount(1, 'data');

        $order = $response->json('data.0');

        $this->assertSame('SO-0001', $order['code']);
        $this->assertSame('Order 1', $order['label']);
        $this->assertSame('REF-001', $order['customer_reference']);
        $this->assertSame(1, $order['statu']);
        $this->assertSame($this->company->label, $order['company']['label']);
        $this->assertSame($this->company->code, $order['company']['code']);
        $this->assertSame($this->company->id, $order['company']['id']);
        $expectedContactLabel = trim(implode(' ', array_filter([
            $this->contact->civility,
            $this->contact->first_name,
            $this->contact->name,
        ])));
        $this->assertSame($this->contact->id, $order['company_contact']['id']);
        $this->assertSame($expectedContactLabel, $order['company_contact']['label']);
        $this->assertSame($this->contact->mail, $order['company_contact']['email']);
        $this->assertSame($this->address->label, $order['company_address']['label']);
        $this->assertSame($this->address->city, $order['company_address']['city']);
        $this->assertSame($this->user->name, $order['user']['name']);
        $this->assertSame($this->paymentCondition->label, $order['payment_condition']['label']);
        $this->assertSame($this->paymentCondition->code, $order['payment_condition']['code']);
        $this->assertSame($this->paymentMethod->label, $order['payment_method']['label']);
        $this->assertSame($this->delivery->label, $order['delivery_method']['label']);
        $this->assertSame('2024-01-15', $order['validity_date']);
        $this->assertSame('2024-01-10 08:00:00', $order['created_at']);
        $this->assertSame('2024-01-10 08:00:00', $order['updated_at']);
        $this->assertNull($order['quote']);
        $this->assertArrayNotHasKey('order_lines', $order);
    }

    public function test_can_include_order_lines_in_response(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 2, 1, 9, 0, 0));

        $order = $this->createOrderWithLines([
            'code' => 'SO-0001',
            'label' => 'Composite Order',
            'customer_reference' => 'REF-100',
        ]);

        OrderLines::create([
            'orders_id' => $order->id,
            'ordre' => 2,
            'code' => 'LINE-2',
            'product_id' => 'SKU-2',
            'label' => 'Second line',
            'qty' => 2,
            'delivered_qty' => 0,
            'delivered_remaining_qty' => 2,
            'invoiced_qty' => 0,
            'invoiced_remaining_qty' => 2,
            'methods_units_id' => $this->unit->id,
            'selling_price' => 12,
            'discount' => 5,
            'accounting_vats_id' => $this->vat->id,
            'internal_delay' => Carbon::create(2024, 2, 10),
            'delivery_date' => Carbon::create(2024, 2, 15),
            'tasks_status' => 1,
            'delivery_status' => 1,
            'invoice_status' => 1,
        ]);

        $firstLine = $order->OrderLines()->orderBy('ordre')->first();

        Task::create([
            'code' => 'TASK-1',
            'label' => 'Operation 1',
            'ordre' => 10,
            'order_lines_id' => $firstLine->id,
            'methods_services_id' => $this->service->id,
            'seting_time' => 0.5,
            'unit_time' => 1.5,
            'status_id' => 1,
            'type' => 1,
            'qty' => 1,
            'qty_init' => 1,
            'qty_aviable' => 1,
            'unit_cost' => 5,
            'unit_price' => 12,
            'methods_units_id' => $this->unit->id,
            'to_schedule' => 1,
            'start_date' => Carbon::create(2024, 2, 5, 8, 0, 0),
            'end_date' => Carbon::create(2024, 2, 6, 17, 0, 0),
            'not_recalculate' => 0,
            'material' => 'Steel',
            'thickness' => 2.5,
            'weight' => 1.2,
        ]);

        $this->actingAs($this->user, 'api');
        $response = $this->getJson('/api/exports/sales-orders?include_lines=1');

        $response->assertOk();
        $response->assertJson(['meta' => ['count' => 1]]);

        $payload = $response->json('data.0');
        $this->assertCount(2, $payload['order_lines']);

        $firstLinePayload = $payload['order_lines'][0];
        $this->assertSame('LINE-1', $firstLinePayload['code']);
        $this->assertSame(5.0, (float) $firstLinePayload['qty']);
        $this->assertSame('Unit', $firstLinePayload['unit']['label']);
        $this->assertSame('UNIT', $firstLinePayload['unit']['code']);
        $this->assertSame(20.0, (float) $firstLinePayload['vat']['rate']);
        $this->assertSame('2024-01-25', $firstLinePayload['delivery_date']);
        $this->assertArrayHasKey('tasks', $firstLinePayload);
        $this->assertCount(1, $firstLinePayload['tasks']);

        $taskPayload = $firstLinePayload['tasks'][0];
        $this->assertSame('TASK-1', $taskPayload['code']);
        $this->assertSame(10, $taskPayload['ordre']);
        $this->assertSame(1.5, (float) $taskPayload['unit_time']);
        $this->assertSame('Usinage', $taskPayload['service']['label']);
        $this->assertSame('SRV-001', $taskPayload['service']['code']);
        $this->assertSame('Unit', $taskPayload['unit']['label']);
        $this->assertSame('2024-02-05 08:00:00', $taskPayload['start_date']);

        $secondLinePayload = $payload['order_lines'][1];
        $this->assertSame('LINE-2', $secondLinePayload['code']);
        $this->assertSame(2.0, (float) $secondLinePayload['qty']);
        $this->assertSame(12.0, (float) $secondLinePayload['selling_price']);
        $this->assertSame(5.0, (float) $secondLinePayload['discount']);
        $this->assertSame('2024-02-15', $secondLinePayload['delivery_date']);
        $this->assertEmpty($secondLinePayload['tasks']);
    }

    public function test_can_filter_sales_orders_by_date_and_status(): void
    {
        Carbon::setTestNow(Carbon::create(2023, 12, 1, 9, 0, 0));
        $this->createOrderWithLines([
            'code' => 'SO-0001',
            'label' => 'Historical Order',
            'customer_reference' => 'REF-HIST',
            'statu' => 2,
        ]);

        Carbon::setTestNow(Carbon::create(2024, 1, 10, 10, 0, 0));
        $this->createOrderWithLines([
            'code' => 'SO-0002',
            'label' => 'Active Order',
            'customer_reference' => 'REF-ACT',
            'statu' => 1,
        ]);

        $this->actingAs($this->user, 'api');
        $response = $this->getJson('/api/exports/sales-orders?from=2024-01-01&status=1');

        $response->assertOk();
        $response->assertJson(['meta' => ['count' => 1]]);
        $response->assertJsonCount(1, 'data');

        $order = $response->json('data.0');
        $this->assertSame('SO-0002', $order['code']);
        $this->assertSame(1, $order['statu']);
    }

    private function createOrderWithLines(array $orderOverrides = [], array $lineOverrides = []): Orders
    {
        $orderDefaults = [
            'uuid' => (string) Str::uuid(),
            'code' => 'SO-' . str_pad((string) (Orders::count() + 1), 4, '0', STR_PAD_LEFT),
            'label' => 'Order ' . (Orders::count() + 1),
            'customer_reference' => 'REF-' . (Orders::count() + 1),
            'companies_id' => $this->company->id,
            'companies_contacts_id' => $this->contact->id,
            'companies_addresses_id' => $this->address->id,
            'validity_date' => Carbon::create(2024, 1, 15),
            'statu' => 1,
            'user_id' => $this->user->id,
            'accounting_payment_conditions_id' => $this->paymentCondition->id,
            'accounting_payment_methods_id' => $this->paymentMethod->id,
            'accounting_deliveries_id' => $this->delivery->id,
            'comment' => 'Sample order for export tests',
            'quotes_id' => null,
            'type' => 1,
        ];

        $order = Orders::create(array_merge($orderDefaults, $orderOverrides));

        $lineDefaults = [
            'orders_id' => $order->id,
            'ordre' => 1,
            'code' => 'LINE-1',
            'product_id' => 'SKU-1',
            'label' => 'Line 1',
            'qty' => 5,
            'delivered_qty' => 0,
            'delivered_remaining_qty' => 5,
            'invoiced_qty' => 0,
            'invoiced_remaining_qty' => 5,
            'methods_units_id' => $this->unit->id,
            'selling_price' => 10,
            'discount' => 0,
            'accounting_vats_id' => $this->vat->id,
            'internal_delay' => Carbon::create(2024, 1, 20),
            'delivery_date' => Carbon::create(2024, 1, 25),
            'tasks_status' => 1,
            'delivery_status' => 1,
            'invoice_status' => 1,
        ];

        OrderLines::create(array_merge($lineDefaults, $lineOverrides));

        return $order->fresh(['OrderLines', 'companie']);
    }
}
