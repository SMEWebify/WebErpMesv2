<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Workflow\Orders;
use Illuminate\Support\Facades\DB;

class OrderSiteTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Migrate and seed the database to ensure required references exist
        $this->artisan('migrate', ['--force' => true]);
        $this->seed();
    }

    public function test_order_site_can_be_created(): void
    {
        $order = Orders::factory()->create();

        $response = $this->post(route('orders.sites.store', $order->id), [
            'label' => 'Main site',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('order_sites', [
            'orders_id' => $order->id,
            'label' => 'Main site',
        ]);
    }

    public function test_order_site_implantation_can_be_created(): void
    {
        $order = Orders::factory()->create();

        $this->post(route('orders.sites.store', $order->id), [
            'label' => 'Main site',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $siteId = DB::table('order_sites')->where('orders_id', $order->id)->value('id');

        $response = $this->post(route('orders.sites.implantations.store', [$order->id, $siteId]), [
            'label' => 'Implantation A',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('order_site_implantations', [
            'order_sites_id' => $siteId,
            'label' => 'Implantation A',
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

        $response = $this->post(route('orders.sites.store', $order->id), []);

        $response->assertSessionHasErrors(['label', 'start_date', 'end_date']);
    }
}

