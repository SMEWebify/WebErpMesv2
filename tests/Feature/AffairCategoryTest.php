<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\AffairCategory;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Catégorie d'affaire : portée par l'opportunité, reprise par le devis créé
 * depuis elle, puis par la commande issue du devis ; recopiée à la duplication.
 */
class AffairCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

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
    }

    private function category(string $code = 'DEBIT', string $label = 'Débitmétrie', bool $active = true): AffairCategory
    {
        return AffairCategory::create(['code' => $code, 'label' => $label, 'ordre' => 10, 'active' => $active]);
    }

    /** Opportunité sur le client d'un devis fabriqué, conditions par défaut posées. */
    private function opportunity(?AffairCategory $category): Opportunities
    {
        $client = Quotes::factory()->make();

        AccountingPaymentConditions::whereKey($client->accounting_payment_conditions_id)->update(['default' => 1]);
        AccountingPaymentMethod::whereKey($client->accounting_payment_methods_id)->update(['default' => 1]);
        AccountingDelivery::whereKey($client->accounting_deliveries_id)->update(['default' => 1]);

        return Opportunities::create([
            'uuid'                   => (string) Str::uuid(),
            'companies_id'           => $client->companies_id,
            'companies_contacts_id'  => $client->companies_contacts_id,
            'companies_addresses_id' => $client->companies_addresses_id,
            'user_id'                => $this->user->id,
            'label'                  => 'Affaire test',
            'budget'                 => 1000,
            'statu'                  => 1,
            'probality'              => 50,
            'affair_category_id'     => $category?->id,
        ]);
    }

    private function convert(Quotes $quote): Orders
    {
        $line = QuoteLines::factory()->create(['quotes_id' => $quote->id, 'statu' => 1]);

        $this->postJson(route('quotes.lines.json.store-order', ['quoteId' => $quote->id]), ['line_ids' => [$line->id]])
            ->assertOk();

        return Orders::where('quotes_id', $quote->id)->sole();
    }

    public function test_category_flows_from_opportunity_to_quote_to_order(): void
    {
        $category    = $this->category();
        $opportunity = $this->opportunity($category);

        $this->get(route('opportunities.store.quote', ['id' => $opportunity->id]))->assertRedirect();

        $quote = Quotes::where('opportunities_id', $opportunity->id)->sole();
        $this->assertSame($category->id, $quote->affair_category_id);

        $quote->update(['user_id' => $this->user->id]);
        $order = $this->convert($quote);

        $this->assertSame($category->id, $order->affair_category_id);
    }

    public function test_a_category_chosen_on_the_quote_wins_over_the_opportunity(): void
    {
        $opportunityCategory = $this->category();
        $quoteCategory       = $this->category('PYRO', 'Pyrométrie');
        $opportunity         = $this->opportunity($opportunityCategory);

        $quote = Quotes::factory()->create([
            'opportunities_id'   => $opportunity->id,
            'affair_category_id' => $quoteCategory->id,
            'user_id'            => $this->user->id,
            'statu'              => 1,
        ]);

        $this->assertSame($quoteCategory->id, $quote->affair_category_id);
        $this->assertSame($quoteCategory->id, $this->convert($quote)->affair_category_id);
    }

    public function test_documents_without_category_stay_without_category(): void
    {
        $opportunity = $this->opportunity(null);
        $quote = Quotes::factory()->create(['opportunities_id' => $opportunity->id, 'user_id' => $this->user->id, 'statu' => 1]);

        $this->assertNull($quote->affair_category_id);
        $this->assertNull($this->convert($quote)->affair_category_id);
    }

    public function test_duplicating_a_quote_keeps_its_category(): void
    {
        $category = $this->category();
        $quote    = Quotes::factory()->create(['affair_category_id' => $category->id, 'statu' => 3]);

        $this->post(route('quotes.duplicate', ['id' => $quote->id]))->assertRedirect();

        $copy = Quotes::where('id', '!=', $quote->id)->sole();
        $this->assertSame($category->id, $copy->affair_category_id);
    }

    public function test_a_quote_started_from_a_template_takes_the_template_category_unless_one_is_chosen(): void
    {
        $category = $this->category();
        $other    = $this->category('MECA', 'Mécanique');
        $source   = Quotes::factory()->create(['affair_category_id' => $category->id]);

        $this->post(route('quotes.save-template', ['id' => $source->id]), ['template_label' => 'Trame débitmètre'])->assertRedirect();
        $template = Quotes::onlyTemplates()->latest('id')->firstOrFail();
        $this->assertSame($category->id, $template->affair_category_id);

        $client  = Quotes::factory()->make();
        $payload = [
            'label'                            => 'Débitmètre client X',
            'companies_id'                     => $client->companies_id,
            'companies_contacts_id'            => $client->companies_contacts_id,
            'companies_addresses_id'           => $client->companies_addresses_id,
            'accounting_payment_conditions_id' => $client->accounting_payment_conditions_id,
            'accounting_payment_methods_id'    => $client->accounting_payment_methods_id,
            'accounting_deliveries_id'         => $client->accounting_deliveries_id,
            'user_id'                          => $this->user->id,
            'template_id'                      => $template->id,
        ];

        $this->postJson(route('quotes.json.store'), $payload + ['code' => 'QT-TPL-1'])->assertCreated();
        $this->assertSame($category->id, Quotes::where('code', 'QT-TPL-1')->value('affair_category_id'));

        $this->postJson(route('quotes.json.store'), $payload + ['code' => 'QT-TPL-2', 'affair_category_id' => $other->id])->assertCreated();
        $this->assertSame($other->id, Quotes::where('code', 'QT-TPL-2')->value('affair_category_id'));
    }

    public function test_index_lists_filter_by_category_and_expose_it(): void
    {
        $category = $this->category();
        $tagged   = Quotes::factory()->create(['affair_category_id' => $category->id, 'statu' => 1]);
        Quotes::factory()->create(['statu' => 1]);

        $all = $this->getJson(route('quotes.json.list', ['statuses' => [1]]))->assertOk();
        $this->assertCount(2, $all->json('data'));
        $this->assertSame([$category->id], array_column($all->json('affair_categories'), 'id'));

        $filtered = $this->getJson(route('quotes.json.list', ['statuses' => [1], 'affair_category_id' => $category->id]))->assertOk();
        $this->assertSame([$tagged->id], array_column($filtered->json('data'), 'id'));
        $this->assertSame('Débitmétrie', $filtered->json('data.0.affair_category.label'));

        $order = Orders::factory()->create(['affair_category_id' => $category->id, 'statu' => 1]);
        Orders::factory()->create(['statu' => 1]);
        $orders = $this->getJson(route('orders.json.list', ['statuses' => [1], 'affair_category_id' => $category->id]))->assertOk();
        $this->assertSame([$order->id], array_column($orders->json('data'), 'id'));
    }

    public function test_an_edit_form_without_the_field_keeps_the_category(): void
    {
        $category = $this->category();
        $quote    = Quotes::factory()->create(['affair_category_id' => $category->id, 'statu' => 1]);

        $this->post(route('quotes.update', ['id' => $quote->id]), [
            'id'                               => $quote->id,
            'label'                            => 'Libellé modifié',
            'companies_id'                     => $quote->companies_id,
            'companies_contacts_id'            => $quote->companies_contacts_id,
            'companies_addresses_id'           => $quote->companies_addresses_id,
            'accounting_payment_conditions_id' => $quote->accounting_payment_conditions_id,
            'accounting_payment_methods_id'    => $quote->accounting_payment_methods_id,
            'accounting_deliveries_id'         => $quote->accounting_deliveries_id,
        ])->assertRedirect();

        $quote->refresh();
        $this->assertSame('Libellé modifié', $quote->label);
        $this->assertSame($category->id, $quote->affair_category_id);
    }

    public function test_settings_screen_requires_the_methods_permission(): void
    {
        $this->get(route('methods.affair-category'))->assertForbidden();
        $this->post(route('methods.affair-category.create'), ['code' => 'DEBIT', 'label' => 'Débitmétrie'])->assertForbidden();
        $this->assertSame(0, AffairCategory::count());

        Permission::firstOrCreate(['name' => 'methods-menu', 'guard_name' => 'web']);
        $this->user->givePermissionTo('methods-menu');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->post(route('methods.affair-category.create'), ['code' => 'DEBIT', 'label' => 'Débitmétrie', 'ordre' => 10])
            ->assertRedirect(route('methods.affair-category'));
        $category = AffairCategory::where('code', 'DEBIT')->sole();
        $this->assertTrue($category->active);

        $this->post(route('methods.affair-category.update', ['id' => $category->id]), ['label' => 'Débitmètres', 'ordre' => 5, 'active' => 0])
            ->assertRedirect(route('methods.affair-category'));
        $category->refresh();
        $this->assertSame('Débitmètres', $category->label);
        $this->assertFalse($category->active);
        $this->assertSame('DEBIT', $category->code);

        $this->get(route('methods.affair-category'))->assertOk()->assertSee('Débitmètres');
    }

    public function test_inactive_categories_are_no_longer_offered_but_stay_on_their_documents(): void
    {
        $active   = $this->category();
        $inactive = $this->category('OLD', 'Ancienne', active: false);

        $this->assertSame([$active->id], AffairCategory::choices()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$active->id, $inactive->id], AffairCategory::choices($inactive->id)->pluck('id')->all());

        $offered = $this->getJson(route('quotes.json.select-data'))->assertOk()->json('affair_categories');
        $this->assertSame([$active->id], array_column($offered, 'id'));
    }
}
