<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use App\Models\Companies\Companies;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsFamilies;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductsSearchJsonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->actingAs(User::factory()->create());

        // ProductsFactory picks existing units and families
        MethodsUnits::factory()->create();
        MethodsFamilies::factory()->create();
    }

    public function test_search_matches_code_or_label(): void
    {
        Products::factory()->create(['code' => 'TOLE-INOX-2', 'label' => 'Tôle 2 mm']);
        Products::factory()->create(['code' => 'ALU-5754', 'label' => 'Platine aluminium']);
        Products::factory()->create(['code' => 'VIS-M6', 'label' => 'Vis inox M6']);

        $response = $this->getJson(route('products.json.search', ['q' => 'inox']));

        $response->assertOk();
        $this->assertEqualsCanonicalizing(['TOLE-INOX-2', 'VIS-M6'], array_column($response->json('products'), 'code'));
    }

    public function test_results_are_limited_to_thirty(): void
    {
        Products::factory()->count(35)->create();

        $this->getJson(route('products.json.search'))
            ->assertOk()
            ->assertJsonCount(30, 'products');
    }

    public function test_supplier_filter_keeps_preferred_supplier_products_only(): void
    {
        $supplier  = Companies::factory()->create();
        $preferred = Products::factory()->create(['code' => 'PREF-1']);
        Products::factory()->create(['code' => 'OTHER-1']);
        $preferred->preferredSuppliers()->attach($supplier->id);

        $response = $this->getJson(route('products.json.search', ['supplier_id' => $supplier->id]));

        $response->assertOk();
        $this->assertSame(['PREF-1'], array_column($response->json('products'), 'code'));
    }

    public function test_quote_lines_select_data_no_longer_sends_the_catalogue(): void
    {
        $quote = Quotes::factory()->create();

        $this->getJson(route('quotes.lines.json.select-data', ['quoteId' => $quote->id]))
            ->assertOk()
            ->assertJsonMissingPath('products');
    }
}
