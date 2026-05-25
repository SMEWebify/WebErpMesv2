<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Workflow\Orders;
use Illuminate\Support\Facades\DB;

class OrderSiteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->actingAs(User::factory()->create());
    }

    public function test_order_site_can_be_created(): void
    {
        $order = Orders::factory()->create();

        $response = $this->post(route('orders.site.store', $order->id), [
            'name' => 'Main site',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('order_sites', [
            'order_id' => $order->id,
            'label' => 'Main site',
        ]);
    }

    public function test_order_site_implantation_can_be_created(): void
    {
        $order = Orders::factory()->create();

        $this->post(route('orders.site.store', $order->id), [
            'name' => 'Main site',
        ]);

        $siteId = DB::table('order_sites')->where('order_id', $order->id)->value('id');

        $response = $this->post(route('orders.site.implantation.store', [$order->id, $siteId]), [
            'name' => 'Implantation A',
            'description' => 'Test',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('order_site_implantations', [
            'order_sites_id' => $siteId,
            'name' => 'Implantation A',
        ]);
    }

    public function test_orders_show_contains_chantier_tab(): void
    {
        $order = Orders::factory()->create();

        $response = $this->get(route('orders.show', ['id' => $order->id]));

        $response->assertStatus(200);
        $response->assertSee(__('general_content.construction_site_trans_key'));
    }

    public function test_order_site_requires_mandatory_fields(): void
    {
        $order = Orders::factory()->create();

        $response = $this->post(route('orders.site.store', $order->id), []);

        $response->assertStatus(302);
    }
}

