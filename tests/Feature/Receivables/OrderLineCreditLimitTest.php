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
use App\Models\Methods\MethodsUnits;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Plafond d'encours sur les lignes d'une commande déjà créée : une commande créée
 * vide (contrôlée à 0) puis remplie ne doit plus échapper au plafond.
 */
class OrderLineCreditLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Companies $company;
    private Orders $order;
    private AccountingVat $vat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        config(['receivables.include_open_orders' => true]);
        Permission::findOrCreate('credit-limit-override');
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->vat = AccountingVat::factory()->create(['rate' => 20, 'default' => 1]);
        MethodsUnits::factory()->create(['default' => 1]);

        // Plafond 1 000 € TTC, blocage, aucune facture due.
        $this->company = Companies::factory()->create([
            'statu_customer' => 2, 'credit_limit' => 1000, 'credit_block_level' => 'block',
        ]);
        $this->order = Orders::factory()->create([
            'companies_id' => $this->company->id, 'type' => 1, 'statu' => 1,
            'companies_contacts_id'  => CompaniesContacts::factory()->create(['companies_id' => $this->company->id])->id,
            'companies_addresses_id' => CompaniesAddresses::factory()->create(['companies_id' => $this->company->id])->id,
        ]);
    }

    private function line(float $price, array $extra = []): array
    {
        return $extra + [
            'ordre' => 1, 'label' => 'Poinçon', 'qty' => 1, 'selling_price' => $price, 'discount' => 0,
            'accounting_vats_id' => $this->vat->id,
        ];
    }

    private function store(float $price, array $extra = [])
    {
        return $this->postJson(route('orders.lines.json.store', ['orderId' => $this->order->id]), $this->line($price, $extra));
    }

    public function test_a_line_under_the_limit_is_added(): void
    {
        // 500 HT = 600 TTC < 1 000
        $this->store(500)->assertCreated();
    }

    public function test_a_line_over_the_limit_is_refused(): void
    {
        // 1 000 HT = 1 200 TTC > 1 000
        $this->store(1000)->assertStatus(409)->assertJsonPath('credit_limit.additional', 1200);

        $this->assertSame(0, OrderLines::where('orders_id', $this->order->id)->count());
    }

    public function test_lines_added_one_by_one_are_counted_together(): void
    {
        $this->store(500)->assertCreated();
        // 600 TTC déjà commandés + 600 TTC = 1 200 > 1 000
        $this->store(500, ['ordre' => 2])->assertStatus(409);
    }

    public function test_override_adds_the_line_and_is_traced_on_the_order(): void
    {
        $this->user->givePermissionTo('credit-limit-override');

        $this->store(1000, ['credit_override' => 1, 'credit_override_reason' => 'Acompte reçu ce matin'])->assertCreated();

        $override = CreditLimitOverride::firstOrFail();
        $this->assertSame($this->order->id, (int) $override->document_id);
        $this->assertSame('Acompte reçu ce matin', $override->reason);
    }

    public function test_raising_a_line_over_the_limit_is_refused_but_lowering_it_is_not(): void
    {
        $id = $this->store(500)->assertCreated()->json('line.id');
        $url = route('orders.lines.json.update', ['orderId' => $this->order->id, 'id' => $id]);

        $this->putJson($url, $this->line(1000))->assertStatus(409);
        $this->assertEquals(500, OrderLines::findOrFail($id)->selling_price);

        $this->putJson($url, $this->line(300))->assertOk();
        $this->assertEquals(300, OrderLines::findOrFail($id)->selling_price);
    }

    public function test_duplicating_a_line_over_the_limit_is_refused(): void
    {
        $id = $this->store(500)->assertCreated()->json('line.id');

        $this->postJson(route('orders.lines.json.duplicate', ['orderId' => $this->order->id, 'id' => $id]))
            ->assertStatus(409);
        $this->assertSame(1, OrderLines::where('orders_id', $this->order->id)->count());
    }

    public function test_internal_order_is_never_checked(): void
    {
        $this->order->update(['type' => 2]);

        // Une commande interne n'accepte pas de ligne par cet écran (403), sans passer par le plafond.
        $this->store(5000)->assertForbidden();
    }

    public function test_company_without_limit_is_not_checked(): void
    {
        $this->company->update(['credit_limit' => null]);

        $this->store(5000)->assertCreated();
    }

    // ------------------------------------------------------------------
    // Conditions de règlement propres au client
    // ------------------------------------------------------------------

    public function test_company_payment_defaults_are_offered_on_new_documents(): void
    {
        AccountingPaymentConditions::factory()->create(['default' => 1]);
        AccountingPaymentMethod::factory()->create(['default' => 1]);
        AccountingDelivery::factory()->create(['default' => 1]);
        $sixtyDays = AccountingPaymentConditions::factory()->create(['default' => 0]);
        $transfer  = AccountingPaymentMethod::factory()->create(['default' => 0]);
        $this->company->update(['accounting_payment_conditions_id' => $sixtyDays->id, 'accounting_payment_methods_id' => $transfer->id]);

        $this->getJson(route('quotes.json.addresses', ['companyId' => $this->company->id]))
            ->assertOk()
            ->assertJson(['payment_condition_id' => $sixtyDays->id, 'payment_method_id' => $transfer->id]);

        $this->assertSame(['conditions' => $sixtyDays->id, 'methods' => $transfer->id], $this->company->fresh()->paymentDefaults());

        $other = Companies::factory()->create();
        $this->assertSame(
            AccountingPaymentConditions::where('default', 1)->value('id'),
            $other->paymentDefaults()['conditions']
        );
    }

    public function test_company_form_saves_payment_defaults(): void
    {
        $sixtyDays = AccountingPaymentConditions::factory()->create();

        $this->postJson(route('companies.json.update', ['company' => $this->company->id]), [
            'code' => $this->company->code, 'label' => $this->company->label,
            'accounting_payment_conditions_id' => $sixtyDays->id,
        ])->assertOk();

        $this->assertSame($sixtyDays->id, (int) $this->company->fresh()->accounting_payment_conditions_id);
    }
}
