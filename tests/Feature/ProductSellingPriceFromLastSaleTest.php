<?php

namespace Tests\Feature;

use App\Models\Companies\Companies;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\ProductPriceHistory;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Bouton « dernier prix » de la fiche produit : le prix de vente reprend le prix
 * brut de la ligne de commande client ou de facture la plus récente.
 */
class ProductSellingPriceFromLastSaleTest extends TestCase
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
        $this->product = Products::factory()->create(['selling_price' => 10]);
        $this->company = Companies::factory()->create();
    }

    private function orderLine(float $price, string $at, array $order = [], array $line = []): OrderLines
    {
        $o = Orders::factory()->create(array_merge(['companies_id' => $this->company->id, 'statu' => 1, 'type' => 1], $order));

        $l = OrderLines::factory()->create(array_merge([
            'orders_id' => $o->id, 'product_id' => $this->product->id, 'line_type' => 'article',
            'selling_price' => $price, 'discount' => 15,
        ], $line));
        $l->forceFill(['created_at' => $at])->saveQuietly();

        return $l;
    }

    private function invoiceLine(array $attributes, string $at, int $type = 1): InvoiceLines
    {
        $invoice = Invoices::factory()->create(['companies_id' => $this->company->id, 'statu' => 1, 'invoice_type' => $type]);

        $l = InvoiceLines::create(array_merge([
            'invoices_id' => $invoice->id, 'label' => 'Pièce', 'ordre' => 10, 'qty' => 1, 'discount' => 20, 'vat_rate' => 20,
        ], $attributes));
        $l->forceFill(['created_at' => $at])->saveQuietly();

        return $l;
    }

    private function apply()
    {
        return $this->post(route('products.selling-price.from-last-sale', ['id' => $this->product->id]));
    }

    public function test_the_most_recent_order_line_gives_its_gross_price(): void
    {
        $this->orderLine(20, '2026-01-10');
        $this->orderLine(25, '2026-03-10');

        $this->apply()->assertRedirect(route('products.show', ['id' => $this->product->id]));

        // Prix brut : la remise de 15 % n'est pas déduite.
        $this->assertSame(25.0, (float) $this->product->fresh()->selling_price);
    }

    public function test_a_more_recent_invoice_line_wins_over_the_order(): void
    {
        $this->orderLine(25, '2026-03-10');
        $this->invoiceLine(['product_id' => $this->product->id, 'unit_price' => 31], '2026-04-01');

        $this->apply();

        $this->assertSame(31.0, (float) $this->product->fresh()->selling_price);
    }

    public function test_an_invoice_line_without_product_is_found_through_its_order_line(): void
    {
        $orderLine = $this->orderLine(25, '2026-03-10');
        // Ligne antérieure au snapshot : ni produit ni prix, tout est lu sur la commande.
        $this->invoiceLine(['order_line_id' => $orderLine->id], '2026-04-01');
        $this->orderLine(18, '2026-03-20', line: ['product_id' => null]);

        $this->apply();

        $this->assertSame(25.0, (float) $this->product->fresh()->selling_price);
    }

    public function test_cancelled_internal_orders_and_proformas_are_ignored(): void
    {
        $this->orderLine(25, '2026-03-10');
        $this->orderLine(99, '2026-04-01', ['statu' => 6]);
        $this->orderLine(98, '2026-04-02', ['type' => 2]);
        $this->invoiceLine(['product_id' => $this->product->id, 'unit_price' => 97], '2026-04-03', type: 2);

        $this->apply();

        $this->assertSame(25.0, (float) $this->product->fresh()->selling_price);
    }

    public function test_the_previous_price_is_kept_in_the_price_history(): void
    {
        $this->orderLine(25, '2026-03-10');

        $this->apply();

        $this->assertTrue(ProductPriceHistory::where('products_id', $this->product->id)
            ->where('type', 'sale')->where('price', 25)->exists());
    }

    public function test_nothing_changes_without_any_sale(): void
    {
        $this->apply()->assertSessionHasErrors('message');

        $this->assertSame(10.0, (float) $this->product->fresh()->selling_price);
    }
}
