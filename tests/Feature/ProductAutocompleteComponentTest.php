<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Products\Products;
use App\Models\Methods\MethodsFamilies;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductAutocompleteComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);
        $this->actingAs(User::factory()->create());

        // ProductsFactory picks existing families (units are created by TestCase)
        MethodsFamilies::factory()->create();
    }

    public function test_component_renders_a_search_field_without_the_catalogue(): void
    {
        $product = Products::factory()->create(['code' => 'CATALOGUE-001']);

        $view = $this->withViewErrors([])
            ->blade('<x-product-autocomplete name="product_id" label="Produit" />');

        $view->assertSee(route('products.json.search'), false)
            ->assertSee('name="product_id"', false)
            ->assertDontSee($product->code);
    }

    public function test_component_shows_the_selected_product(): void
    {
        $product = Products::factory()->create(['code' => 'SELECTED-001', 'label' => 'Platine']);

        $view = $this->withViewErrors([])
            ->blade('<x-product-autocomplete name="product_id" :selected="$product" />', ['product' => $product]);

        $view->assertSee('SELECTED-001 — Platine')
            ->assertSee('value="' . $product->id . '"', false);
    }

    public function test_amdec_page_no_longer_renders_every_product(): void
    {
        Products::factory()->create(['code' => 'AMDEC-HIDDEN-001']);

        $this->get(route('quality.amdec'))
            ->assertOk()
            ->assertSee('product-autocomplete', false)
            ->assertDontSee('AMDEC-HIDDEN-001');
    }
}
