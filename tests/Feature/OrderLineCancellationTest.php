<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Models\User;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Packaging;
use App\Models\Workflow\PackagingLine;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Annulation d'une ligne de commande (reste non livré) et d'une ligne de BL, et
 * refus de supprimer une ligne de commande déjà livrée : sa suppression laissait
 * un BL dont le PDF ne s'imprimait plus.
 */
class OrderLineCancellationTest extends TestCase
{
    private AccountingVat $vat;
    private Companies $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        $this->actingAs(User::factory()->create());
        $this->vat = AccountingVat::factory()->create(['rate' => 20]);
        $this->company = Companies::factory()->create();
    }

    // ------------------------------------------------------------------
    // Fabriques
    // ------------------------------------------------------------------

    private function order(array $attributes = []): Orders
    {
        return Orders::factory()->create($attributes + ['companies_id' => $this->company->id, 'type' => 1, 'statu' => 2]);
    }

    private function line(Orders $order, float $qty, float $delivered = 0, array $attributes = []): OrderLines
    {
        return OrderLines::factory()->create($attributes + [
            'orders_id' => $order->id, 'code' => 'PIECE-' . fake()->unique()->numberBetween(1, 99999), 'label' => 'Guide',
            'qty' => $qty, 'delivered_qty' => $delivered, 'delivered_remaining_qty' => $qty - $delivered,
            'invoiced_qty' => 0, 'invoiced_remaining_qty' => $qty,
            'delivery_status' => $delivered <= 0 ? 1 : ($delivered < $qty ? 2 : 3), 'invoice_status' => 1,
            'line_type' => 'article', 'selling_price' => 50, 'discount' => 0, 'accounting_vats_id' => $this->vat->id,
            'use_calculated_price' => false,
        ]);
    }

    private function delivery(): Deliverys
    {
        return Deliverys::factory()->create([
            'code' => 'BL-' . fake()->unique()->numberBetween(1, 99999), 'companies_id' => $this->company->id,
            'statu' => 1, 'user_id' => auth()->id(),
        ]);
    }

    private function deliveryLine(Deliverys $delivery, OrderLines $orderLine, float $qty): DeliveryLines
    {
        return DeliveryLines::create([
            'deliverys_id' => $delivery->id, 'order_line_id' => $orderLine->id, 'ordre' => 10, 'qty' => $qty, 'statu' => 1,
        ]);
    }

    private function cancelOrderLine(OrderLines $line, ?string $reason = 'Annulée par le client')
    {
        return $this->postJson(route('orders.lines.json.cancel', ['orderId' => $line->orders_id, 'id' => $line->id]), array_filter(['reason' => $reason]));
    }

    private function cancelDeliveryLine(DeliveryLines $line, ?string $reason = 'Ligne mise par erreur')
    {
        return $this->postJson(route('deliverys.line.cancel', ['id' => $line->deliverys_id, 'lineId' => $line->id]), array_filter(['reason' => $reason]));
    }

    // ------------------------------------------------------------------
    // Ligne de commande
    // ------------------------------------------------------------------

    public function test_cancelling_a_line_cancels_the_undelivered_remainder_only(): void
    {
        $line = $this->line($this->order(), 10, 4);

        $this->cancelOrderLine($line)->assertOk()
            ->assertJsonPath('line.cancellation.cancelled', true)
            ->assertJsonPath('line.cancellation.can_cancel', false);

        $line->refresh();
        $this->assertEquals(4, (float) $line->qty);
        $this->assertEquals(6, (float) $line->cancelled_qty);
        $this->assertEquals(0, (float) $line->delivered_remaining_qty);
        $this->assertEquals(4, (float) $line->delivered_qty);
        $this->assertEquals(4, (float) $line->invoiced_remaining_qty, 'le livré reste à facturer');
        $this->assertSame(3, (int) $line->delivery_status);
        $this->assertSame(1, (int) $line->invoice_status);
        $this->assertSame('Annulée par le client', $line->cancel_reason);

        $log = Activity::where('description', 'order_line_cancelled')->where('subject_id', $line->id)->firstOrFail();
        $this->assertEquals(6, $log->properties['cancelled_qty']);
    }

    public function test_a_reason_is_required(): void
    {
        $line = $this->line($this->order(), 2);

        $this->cancelOrderLine($line, null)->assertStatus(422)
            ->assertJsonPath('message', __('order_line_cancel.errors.reason_required'));

        $this->assertNull($line->fresh()->cancelled_at);
    }

    public function test_a_fully_delivered_line_cannot_be_cancelled(): void
    {
        $line = $this->line($this->order(), 2, 2);

        $this->cancelOrderLine($line)->assertStatus(422)
            ->assertJsonPath('message', __('order_line_cancel.errors.fully_delivered'));
    }

    public function test_cancelling_closes_the_open_tasks_of_the_line(): void
    {
        Status::create(['title' => 'Open', 'order' => 1]);
        $finished = Status::create(['title' => 'Finished', 'order' => 2]);
        $line = $this->line($this->order(), 2);
        $task = Task::factory()->create([
            'order_lines_id' => $line->id, 'quote_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'status_id' => Status::where('title', 'Open')->value('id'),
        ]);

        $this->cancelOrderLine($line)->assertOk();

        $this->assertSame($finished->id, (int) $task->fresh()->status_id);
    }

    public function test_the_order_becomes_delivered_when_the_last_open_line_is_cancelled(): void
    {
        $order = $this->order(['statu' => 4]);
        $this->line($order, 3, 3);
        $open = $this->line($order, 5, 1);

        $this->cancelOrderLine($open)->assertOk();

        $this->assertSame(3, (int) $order->fresh()->statu);
    }

    public function test_a_delivered_line_cannot_be_deleted(): void
    {
        $order = $this->order();
        $line = $this->line($order, 2);
        $this->deliveryLine($this->delivery(), $line, 1);
        $line->forceFill(['delivered_qty' => 1, 'delivered_remaining_qty' => 1])->save();

        $this->deleteJson(route('orders.lines.json.destroy', ['orderId' => $order->id, 'id' => $line->id]))
            ->assertStatus(422)
            ->assertJsonPath('message', __('order_line_cancel.errors.delete_used'))
            ->assertJsonPath('can_cancel', true);

        $this->assertNotNull($line->fresh());
    }

    public function test_an_untouched_line_can_still_be_deleted(): void
    {
        $order = $this->order();
        $line = $this->line($order, 2);

        $this->deleteJson(route('orders.lines.json.destroy', ['orderId' => $order->id, 'id' => $line->id]))->assertOk();

        $this->assertNull($line->fresh());
    }

    // ------------------------------------------------------------------
    // Ligne de BL
    // ------------------------------------------------------------------

    public function test_cancelling_a_delivery_line_gives_the_quantity_back_to_the_order(): void
    {
        $order = $this->order(['statu' => 3]);
        $line = $this->line($order, 2, 2);
        $delivery = $this->delivery();
        $deliveryLine = $this->deliveryLine($delivery, $line, 2);

        $this->cancelDeliveryLine($deliveryLine)->assertOk()->assertJsonPath('stock_restored', 0);

        $this->assertNull(DeliveryLines::find($deliveryLine->id));
        $line->refresh();
        $this->assertEquals(0, (float) $line->delivered_qty);
        $this->assertEquals(2, (float) $line->delivered_remaining_qty);
        $this->assertSame(1, (int) $line->delivery_status);
        $this->assertSame(2, (int) $order->fresh()->statu, 'plus rien de livré : commande en cours');

        $log = Activity::where('description', 'delivery_line_cancelled')->where('subject_id', $delivery->id)->firstOrFail();
        $this->assertSame('Ligne mise par erreur', $log->properties['reason']);
    }

    public function test_a_stock_exit_made_by_the_delivery_is_put_back_at_the_current_cost(): void
    {
        MethodsUnits::factory()->create(['code' => 'U', 'label' => 'Unité', 'default' => 1]);
        MethodsFamilies::factory()->create(['methods_services_id' => MethodsServices::factory()->create(['type' => 1])->id]);
        $product = Products::factory()->create();
        $slp = StockLocationProducts::factory()->create(['products_id' => $product->id, 'unit_cost' => 12.5]);
        StockMove::create(['user_id' => auth()->id(), 'qty' => 10, 'stock_location_products_id' => $slp->id, 'typ_move' => 5, 'component_price' => 12.5]);

        $line = $this->line($this->order(), 3, 3, ['product_id' => $product->id]);
        StockMove::create(['user_id' => auth()->id(), 'qty' => 3, 'stock_location_products_id' => $slp->id, 'order_line_id' => $line->id, 'typ_move' => 9]);
        $deliveryLine = $this->deliveryLine($this->delivery(), $line, 3);

        $this->cancelDeliveryLine($deliveryLine)->assertOk()->assertJsonPath('stock_restored', 3);

        $back = StockMove::where('order_line_id', $line->id)->where('typ_move', 5)->firstOrFail();
        $this->assertEquals(3, (float) $back->qty);
        $this->assertEquals(12.5, (float) $slp->fresh()->unit_cost, 'CUMP inchangé');
    }

    public function test_an_invoiced_delivery_line_cannot_be_cancelled(): void
    {
        $line = $this->line($this->order(), 2, 2);
        $deliveryLine = $this->deliveryLine($this->delivery(), $line, 2);
        $deliveryLine->forceFill(['invoice_status' => 4])->save();

        $this->cancelDeliveryLine($deliveryLine)->assertStatus(422)
            ->assertJsonPath('error', __('order_line_cancel.errors.delivery_invoiced'));

        $this->assertNotNull(DeliveryLines::find($deliveryLine->id));
    }

    public function test_a_packaged_delivery_line_cannot_be_cancelled(): void
    {
        $line = $this->line($this->order(), 2, 2);
        $delivery = $this->delivery();
        $deliveryLine = $this->deliveryLine($delivery, $line, 2);
        $package = Packaging::create([
            'deliverys_id' => $delivery->id, 'code' => 'C-1', 'type' => 'Caisse', 'user_id' => auth()->id(),
            'net_weight' => 10, 'gross_weight' => 12, 'length' => 1000, 'width' => 800, 'height' => 500,
        ]);
        PackagingLine::create(['packaging_id' => $package->id, 'delivery_line_id' => $deliveryLine->id, 'qty' => 2]);

        $this->cancelDeliveryLine($deliveryLine)->assertStatus(422)
            ->assertJsonPath('error', __('order_line_cancel.errors.delivery_packaged'));
    }

    public function test_cancelling_the_delivery_of_a_cancelled_line_adds_to_the_cancelled_part(): void
    {
        $line = $this->line($this->order(), 5, 2);
        $deliveryLine = $this->deliveryLine($this->delivery(), $line, 2);
        $this->cancelOrderLine($line)->assertOk();

        $this->cancelDeliveryLine($deliveryLine)->assertOk();

        $line->refresh();
        $this->assertEquals(0, (float) $line->qty);
        $this->assertEquals(5, (float) $line->cancelled_qty);
        $this->assertEquals(0, (float) $line->delivered_remaining_qty);
        $this->assertEquals(0, (float) $line->invoiced_remaining_qty);
    }

    public function test_a_delivery_line_whose_order_line_is_gone_can_be_cancelled(): void
    {
        $delivery = $this->delivery();
        $orphan = DeliveryLines::create(['deliverys_id' => $delivery->id, 'order_line_id' => 999999, 'ordre' => 10, 'qty' => 1, 'statu' => 1]);

        $this->cancelDeliveryLine($orphan)->assertOk();

        $this->assertNull(DeliveryLines::find($orphan->id));
    }
}
