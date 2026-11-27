<?php

namespace Tests\Feature\Orders;

use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Methods\MethodsUnits;
use App\Models\User;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\OrderConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Dictée d'une commande : même brouillon que pour un devis, lignes créées en fin
 * de commande et marquées « proposé par IA ». Seulement sur une commande client
 * ouverte qui n'a encore aucun ARC.
 */
class OrderDictationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Companies $company;
    private MethodsUnits $unit;
    private MethodsUnits $day;
    private AccountingVat $vat;

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

        app()->forgetInstance('Factory');
        app()->instance('Factory', Factory::create(['name' => 'Métallerie', 'curency' => 'EUR', 'pdf_header_font_color' => '#ddd']));

        MethodsUnits::query()->update(['default' => 0]);
        AccountingVat::query()->update(['default' => 0]);
        $this->unit = MethodsUnits::factory()->create(['code' => 'U', 'label' => 'Unité', 'default' => 1]);
        $this->day = MethodsUnits::factory()->create(['code' => 'J', 'label' => 'Jour', 'default' => 0]);
        $this->vat = AccountingVat::factory()->create(['rate' => 20, 'default' => 1]);

        $this->company = Companies::factory()->create(['statu_customer' => 2]);

        config([
            'ai.providers.claude.api_key' => 'test-key',
            'ai.providers.claude.default_model' => 'claude-haiku-4-5-20251001',
        ]);
    }

    private function order(array $attributes = []): Orders
    {
        return Orders::factory()->create($attributes + [
            'code' => 'CO-0001', 'companies_id' => $this->company->id, 'type' => 1, 'statu' => 1,
            'user_id' => $this->user->id,
            'companies_contacts_id'  => CompaniesContacts::factory()->create(['companies_id' => $this->company->id])->id,
            'companies_addresses_id' => CompaniesAddresses::factory()->create(['companies_id' => $this->company->id])->id,
        ]);
    }

    private function fakeClaude(array $lines): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode(['lines' => $lines])]],
                'usage' => ['input_tokens' => 10, 'output_tokens' => 10],
            ]),
        ]);
    }

    private function store(Orders $order, array $lines, array $extra = [])
    {
        return $this->postJson(route('orders.lines.json.dictation.store', ['orderId' => $order->id]), ['lines' => $lines] + $extra);
    }

    public function test_it_proposes_draft_lines_without_saving_anything(): void
    {
        $order = $this->order();
        $this->fakeClaude([['label' => 'Fabrication en atelier', 'qty' => 2, 'unit' => 'J', 'unit_price' => null]]);

        $this->postJson(route('orders.lines.json.dictation.propose', ['orderId' => $order->id]), ['text' => '2 jours d\'atelier'])
            ->assertOk()
            ->assertJsonPath('lines.0.methods_units_id', $this->day->id);

        $this->assertSame(0, OrderLines::count());
    }

    public function test_validated_lines_are_created_at_the_end_and_marked_as_proposed_by_ai(): void
    {
        $order = $this->order();
        OrderLines::create([
            'orders_id' => $order->id, 'ordre' => 3, 'code' => 'L-1', 'label' => 'Ligne existante',
            'qty' => 1, 'delivered_remaining_qty' => 1, 'invoiced_remaining_qty' => 1, 'selling_price' => 10, 'discount' => 0,
            'methods_units_id' => $this->unit->id, 'accounting_vats_id' => $this->vat->id,
        ]);

        $response = $this->store($order, [
            ['label' => 'Garde-corps acier', 'qty' => 2, 'methods_units_id' => null, 'selling_price' => null, 'material' => 'Acier S235', 'internal_comment' => 'Main courante Ø42'],
            ['label' => 'Pose', 'qty' => 1, 'methods_units_id' => $this->day->id, 'selling_price' => 450],
        ])->assertCreated();

        $this->assertCount(2, $response->json('lines'));
        $this->assertTrue($response->json('lines.0.ai_proposed'));

        $first = OrderLines::where('label', 'Garde-corps acier')->firstOrFail();
        $this->assertSame(4, (int) $first->ordre);
        $this->assertSame($this->unit->id, (int) $first->methods_units_id, 'unité par défaut');
        $this->assertSame($this->vat->id, (int) $first->accounting_vats_id);
        $this->assertEquals(0, $first->getRawOriginal('selling_price'), 'prix non dicté = 0, à saisir');
        $this->assertEquals(2, $first->delivered_remaining_qty, 'reste à livrer = quantité');
        $this->assertEquals(2, $first->invoiced_remaining_qty, 'reste à facturer = quantité');
        $this->assertNotNull($first->ai_proposed_at);

        $details = OrderLineDetails::where('order_lines_id', $first->id)->firstOrFail();
        $this->assertSame('Acier S235', $details->material);
        $this->assertSame('Main courante Ø42', $details->internal_comment);

        $this->assertNull(OrderLines::where('label', 'Ligne existante')->value('ai_proposed_at'));
    }

    public function test_an_order_with_an_acknowledgement_refuses_the_dictation(): void
    {
        $order = $this->order();
        app(OrderConfirmationService::class)->createFromOrder($order);
        Http::fake();

        $this->postJson(route('orders.lines.json.dictation.propose', ['orderId' => $order->id]), ['text' => 'garde-corps'])
            ->assertForbidden()
            ->assertJsonPath('message', __('quote_dictation.order_refused_confirmation'));
        $this->store($order, [['label' => 'Garde-corps', 'qty' => 1]])->assertForbidden();

        Http::assertNothingSent();
        $this->assertSame(0, OrderLines::where('ai_proposed_at', '!=', null)->count());
    }

    public function test_an_order_that_is_no_longer_open_refuses_the_dictation(): void
    {
        $order = $this->order(['statu' => 2]);
        Http::fake();

        $this->postJson(route('orders.lines.json.dictation.propose', ['orderId' => $order->id]), ['text' => 'garde-corps'])
            ->assertForbidden()
            ->assertJsonPath('message', __('quote_dictation.order_refused_status'));
        $this->store($order, [['label' => 'Garde-corps', 'qty' => 1]])->assertForbidden();

        Http::assertNothingSent();
        $this->assertSame(0, OrderLines::count());
    }

    public function test_an_internal_order_refuses_the_dictation(): void
    {
        $order = $this->order(['type' => 2]);

        $this->store($order, [['label' => 'Garde-corps', 'qty' => 1]])
            ->assertForbidden()
            ->assertJsonPath('message', __('quote_dictation.order_refused_internal'));

        $this->assertSame(0, OrderLines::count());
    }

    public function test_the_credit_limit_applies_to_dictated_lines(): void
    {
        config(['receivables.include_open_orders' => true]);
        // Plafond 1 000 € TTC, blocage.
        $this->company->update(['credit_limit' => 1000, 'credit_block_level' => 'block']);
        $order = $this->order();

        // 2 × 500 HT = 1 200 TTC > 1 000
        $this->store($order, [['label' => 'Garde-corps', 'qty' => 2, 'selling_price' => 500]])
            ->assertStatus(409)
            ->assertJsonPath('credit_limit.additional', 1200);
        $this->assertSame(0, OrderLines::count());

        // Prix non dicté (0) : rien n'est ajouté à l'encours.
        $this->store($order, [['label' => 'Garde-corps', 'qty' => 2]])->assertCreated();
    }

    public function test_a_duplicated_line_does_not_keep_the_ai_mark(): void
    {
        $order = $this->order();
        $this->store($order, [['label' => 'Garde-corps', 'qty' => 1]])->assertCreated();
        $line = OrderLines::firstOrFail();

        $this->postJson(route('orders.lines.json.duplicate', ['orderId' => $order->id, 'id' => $line->id]))
            ->assertCreated()
            ->assertJsonPath('line.ai_proposed', false);
    }
}
