<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Planning\Task;
use App\Models\Planning\SubAssembly;
use App\Models\Products\Products;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Services\Planning\BillOfMaterialsCopier;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Chaque chemin qui recopie une gamme doit reprendre la nomenclature sur
 * toute sa profondeur : les niveaux inférieurs ne portent que
 * sub_assembly_id et étaient perdus par les anciennes copies.
 */
class BillOfMaterialsCopyTest extends TestCase
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

        // ProductsFactory pioche une unité et une famille existantes.
        \App\Models\Methods\MethodsUnits::factory()->create();
        \App\Models\Methods\MethodsFamilies::factory()->create();
    }

    /**
     * Une opération de premier niveau, un sous-ensemble, un sous-ensemble
     * enfant et une opération sous cet enfant.
     */
    private function buildBom(string $key, int $id): void
    {
        $blank = ['quote_lines_id' => null, 'order_lines_id' => null, 'products_id' => null];

        Task::factory()->create(array_merge($blank, [$key => $id]));
        $root  = SubAssembly::create(['ordre' => 1, $key => $id, 'child_id' => 1, 'qty' => 2, 'unit_price' => 10]);
        $child = SubAssembly::create(['ordre' => 1, 'sub_assembly_id' => $root->id, 'child_id' => 1, 'qty' => 3, 'unit_price' => 5]);
        Task::factory()->create(array_merge($blank, ['sub_assembly_id' => $child->id]));
    }

    private function assertFullBom(string $key, int $id): void
    {
        $this->assertSame(1, Task::where($key, $id)->whereNull('sub_assembly_id')->count(), "opération de premier niveau sur {$key}={$id}");

        $root  = SubAssembly::where($key, $id)->sole();
        $child = SubAssembly::where('sub_assembly_id', $root->id)->sole();
        $this->assertSame(1, Task::where('sub_assembly_id', $child->id)->count(), "opération sous le sous-ensemble enfant de {$key}={$id}");
    }

    public function test_product_bom_is_fully_copied_onto_another_product_keeping_origin(): void
    {
        $source = Products::factory()->create();
        $target = Products::factory()->create();
        $this->buildBom('products_id', $source->id);
        Task::where('products_id', $source->id)->update(['origin' => '1']);

        app(BillOfMaterialsCopier::class)->copy('products_id', $source->id, 'products_id', $target->id);

        $this->assertFullBom('products_id', $target->id);
        $this->assertFullBom('products_id', $source->id);
        $this->assertSame('1', (string) Task::where('products_id', $target->id)->value('origin'));
    }

    public function test_breakdown_copies_the_product_bom_onto_the_order_line_with_first_status(): void
    {
        $product = Products::factory()->create();
        $this->buildBom('products_id', $product->id);
        \App\Models\Planning\Status::create(['title' => 'Open', 'order' => 1]);

        $order = Orders::factory()->create();
        $line  = OrderLines::factory()->create(['orders_id' => $order->id, 'product_id' => $product->id]);

        $this->postJson(route('orders.lines.json.breakdown', ['orderId' => $order->id, 'id' => $line->id]))->assertOk();

        $this->assertFullBom('order_lines_id', $line->id);
        $this->assertNull(SubAssembly::where('order_lines_id', $line->id)->value('products_id'));

        $firstStatus = \App\Models\Planning\Status::orderBy('order')->value('id');
        $copied = Task::where('sub_assembly_id', SubAssembly::where('sub_assembly_id', SubAssembly::where('order_lines_id', $line->id)->value('id'))->value('id'))->sole();
        $this->assertEquals($firstStatus, $copied->status_id);
    }

    public function test_order_line_duplicate_copies_the_full_bom_and_lands_below_the_source(): void
    {
        $order  = Orders::factory()->create();
        $first  = OrderLines::factory()->create(['orders_id' => $order->id, 'ordre' => 1]);
        $second = OrderLines::factory()->create(['orders_id' => $order->id, 'ordre' => 2]);
        $this->buildBom('order_lines_id', $first->id);

        $response = $this->postJson(route('orders.lines.json.duplicate', ['orderId' => $order->id, 'id' => $first->id]));

        $response->assertCreated()->assertJsonPath('line.ordre', 2);
        $this->assertSame(3, (int) $second->fresh()->ordre);
        $this->assertFullBom('order_lines_id', $response->json('line.id'));
        $this->assertFullBom('order_lines_id', $first->id);
    }

    public function test_order_generated_by_a_proforma_carries_the_technical_content(): void
    {
        $company = Companies::factory()->create();
        $quote   = Quotes::factory()->create(['statu' => 1, 'companies_id' => $company->id]);
        $line    = QuoteLines::factory()->create(['quotes_id' => $quote->id]);
        QuoteLineDetails::create(['quote_lines_id' => $line->id, 'material' => 'S235']);
        $this->buildBom('quote_lines_id', $line->id);

        $this->postJson(route('proformas.request.store.quote'), [
            'code'                   => 'PF-TEST-1',
            'label'                  => 'Proforma',
            'quote_id'               => $quote->id,
            'companies_id'           => $company->id,
            'companies_addresses_id' => CompaniesAddresses::factory()->create(['companies_id' => $company->id])->id,
            'companies_contacts_id'  => CompaniesContacts::factory()->create(['companies_id' => $company->id])->id,
            'user_id'                => $this->user->id,
            'lines'                  => [$line->id],
        ])->assertOk();

        $orderLine = OrderLines::where('quote_lines_id', $line->id)->sole();
        $this->assertSame('S235', $orderLine->OrderLineDetails->material);
        $this->assertSame(2, (int) $orderLine->tasks_status);
        $this->assertFullBom('order_lines_id', $orderLine->id);
        $this->assertFullBom('quote_lines_id', $line->id);
    }
}
