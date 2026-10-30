<?php

namespace Tests\Feature;

use App\Models\Companies\Companies;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\CustomerPriceList;
use App\Models\Products\Products;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Onglet « Tarifs » de la fiche article : grille tarifaire client regroupée par portée,
 * ajout / modification / suppression ramenant sur l'onglet.
 */
class ProductCustomerPriceListTest extends TestCase
{
    use RefreshDatabase;

    private Products $product;
    private Companies $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Permission::findOrCreate('products-menu');

        Permission::findOrCreate('products-edit');
        $user = User::factory()->create();
        $user->givePermissionTo(['products-menu', 'products-edit']);
        $this->actingAs($user);

        MethodsUnits::factory()->create();
        MethodsFamilies::factory()->create();
        $this->product = Products::factory()->create(['selling_price' => 10, 'sold' => 1, 'purchased' => 0]);
        $this->company = Companies::factory()->create(['label' => 'ACME Tôlerie']);
    }

    private function prices(): string
    {
        return route('products.show', ['id' => $this->product->id]) . '#Prices';
    }

    public function test_the_prices_tab_groups_breaks_by_scope_with_the_gap_to_the_base_price(): void
    {
        CustomerPriceList::create(['products_id' => $this->product->id, 'min_qty' => 1, 'max_qty' => 9, 'price' => 9]);
        CustomerPriceList::create(['products_id' => $this->product->id, 'min_qty' => 10, 'max_qty' => null, 'price' => 8]);
        CustomerPriceList::create(['products_id' => $this->product->id, 'customer_type' => 2, 'min_qty' => 1, 'price' => 12]);
        CustomerPriceList::create(['products_id' => $this->product->id, 'companies_id' => $this->company->id, 'min_qty' => 100, 'price' => 7.5]);

        $this->get(route('products.show', ['id' => $this->product->id]))
            ->assertOk()
            ->assertSee('id="Prices"', false)
            ->assertSee('id="priceListModal"', false)
            ->assertSeeInOrder([
                __('price_list.scope_all'), '1 – 9', '-10,0 %', '≥ 10', '-20,0 %',
                __('price_list.group_type', ['type' => __('general_content.individual_trans_key')]), '+20,0 %',
                __('price_list.group_company', ['company' => 'ACME Tôlerie']), '≥ 100', '-25,0 %',
            ], false)
            ->assertDontSee(__('price_list.overlap'));
    }

    public function test_overlapping_ranges_are_flagged(): void
    {
        CustomerPriceList::create(['products_id' => $this->product->id, 'min_qty' => 1, 'max_qty' => 20, 'price' => 9]);
        CustomerPriceList::create(['products_id' => $this->product->id, 'min_qty' => 10, 'max_qty' => null, 'price' => 8]);

        $this->get(route('products.show', ['id' => $this->product->id]))
            ->assertOk()
            ->assertSee(__('price_list.overlap'));
    }

    public function test_an_article_not_sold_without_breaks_has_no_prices_tab(): void
    {
        $this->product->update(['sold' => 0]);

        $this->get(route('products.show', ['id' => $this->product->id]))
            ->assertOk()
            ->assertDontSee('id="Prices"', false);
    }

    public function test_store_update_and_destroy_return_to_the_prices_tab(): void
    {
        $this->post(route('products.customer-price-list.store', ['product' => $this->product->id]), [
            'companies_id' => $this->company->id, 'min_qty' => 5, 'max_qty' => '', 'price' => 6.5,
        ])->assertRedirect($this->prices());

        $price = CustomerPriceList::sole();
        $this->assertSame($this->company->id, (int) $price->companies_id);
        $this->assertNull($price->customer_type);
        $this->assertNull($price->max_qty);

        // Passage en portée « type de client » : le client est vidé par l'écran.
        $this->put(route('products.customer-price-list.update', ['product' => $this->product->id, 'priceList' => $price->id]), [
            'companies_id' => '', 'customer_type' => 1, 'min_qty' => 5, 'max_qty' => 50, 'price' => 6,
        ])->assertRedirect($this->prices());

        $price->refresh();
        $this->assertNull($price->companies_id);
        $this->assertSame(1, (int) $price->customer_type);

        $this->delete(route('products.customer-price-list.destroy', ['product' => $this->product->id, 'priceList' => $price->id]))
            ->assertRedirect($this->prices());
        $this->assertDatabaseCount('customer_price_lists', 0);
    }

    public function test_a_refused_entry_reopens_the_window_with_the_typed_values(): void
    {
        $this->from(route('products.show', ['id' => $this->product->id]))
            ->post(route('products.customer-price-list.store', ['product' => $this->product->id]), [
                '_price_list_id' => '', 'min_qty' => 10, 'max_qty' => 5, 'price' => 3,
            ])->assertSessionHasErrors('max_qty', null, 'customerPriceList');

        $this->get(route('products.show', ['id' => $this->product->id]))
            ->assertOk()
            ->assertSee('data-reopen=', false);
    }
}
