<?php

namespace Tests\Feature;

use App\Models\Products\Products;
use App\Models\Products\StockLocation;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\Stocks;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le formulaire de sortie de stock proposait les lignes de commande et les tâches de
 * l'article dont l'id valait celui de la ligne de stock, au lieu de l'article lui-même.
 */
class StockLineShowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function the_dispatch_form_lists_the_order_lines_of_the_stocked_item()
    {
        $this->withoutMiddleware([\App\Http\Middleware\CheckUserRole::class, \App\Http\Middleware\CheckFactory::class]);
        $user = User::factory()->create();
        $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('stock-lot-serial-management'));
        $this->actingAs($user);

        \App\Models\Methods\MethodsUnits::factory()->create();
        \App\Models\Methods\MethodsFamilies::factory()->create();
        $stock = Stocks::create(['code' => 'S1', 'label' => 'Stock', 'user_id' => $user->id]);
        $location = StockLocation::create(['code' => 'L1', 'label' => 'Rack', 'stocks_id' => $stock->id, 'user_id' => $user->id]);
        $other = Products::factory()->create();
        Products::factory()->create();
        $item = Products::factory()->create();
        // Deux lignes de stock : la seconde a l'id 2, l'article stocké un autre id.
        StockLocationProducts::create(['code' => 'SLP0', 'user_id' => $user->id, 'stock_locations_id' => $location->id, 'products_id' => $other->id, 'mini_qty' => 0]);
        $line = StockLocationProducts::create(['code' => 'SLP1', 'user_id' => $user->id, 'stock_locations_id' => $location->id, 'products_id' => $item->id, 'mini_qty' => 0]);
        $this->assertNotSame($line->id, $item->id);

        $mine = OrderLines::factory()->create(['product_id' => $item->id]);
        $unrelated = OrderLines::factory()->create(['product_id' => $line->id]);

        $this->get(route('products.stockline.show', ['id' => $line->id]))
            ->assertOk()
            ->assertViewHas('OrderLineList', fn ($list) => $list->contains('id', $mine->id) && ! $list->contains('id', $unrelated->id));
    }
}
