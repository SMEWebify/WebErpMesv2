<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Workflow\Orders;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderRating;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Note client d'une commande (route publique /order/ratings) : commande reconnue
 * par son UUID seulement, société de la commande, une seule note.
 */
class OrderRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_rates_an_order_by_its_uuid(): void
    {
        $order = Orders::factory()->create();

        $this->post(route('order.ratings.store'), [
            'uuid' => $order->uuid,
            'rating' => 4,
            'comment' => 'Livraison à l\'heure',
        ])->assertRedirect();

        $this->assertDatabaseHas('order_ratings', [
            'orders_id' => $order->id,
            'companies_id' => $order->companies_id,
            'rating' => 4,
        ]);
    }

    public function test_order_and_company_ids_sent_by_the_client_are_ignored(): void
    {
        $order = Orders::factory()->create();
        $other = Orders::factory()->create();
        $company = Companies::factory()->create();

        $this->post(route('order.ratings.store'), [
            'uuid' => $order->uuid,
            'orders_id' => $other->id,
            'companies_id' => $company->id,
            'rating' => 2,
        ])->assertRedirect();

        $this->assertSame(1, OrderRating::count());
        $rating = OrderRating::first();
        $this->assertSame($order->id, (int) $rating->orders_id);
        $this->assertSame($order->companies_id, (int) $rating->companies_id);
    }

    public function test_an_order_id_without_uuid_is_refused(): void
    {
        $order = Orders::factory()->create();

        $this->post(route('order.ratings.store'), [
            'orders_id' => $order->id,
            'companies_id' => $order->companies_id,
            'rating' => 1,
        ])->assertSessionHasErrors('uuid');

        $this->assertSame(0, OrderRating::count());
    }

    public function test_an_unknown_uuid_is_not_found(): void
    {
        $this->post(route('order.ratings.store'), [
            'uuid' => '00000000-0000-0000-0000-000000000000',
            'rating' => 5,
        ])->assertNotFound();

        $this->assertSame(0, OrderRating::count());
    }

    public function test_an_order_is_rated_only_once(): void
    {
        $order = Orders::factory()->create();

        $this->post(route('order.ratings.store'), ['uuid' => $order->uuid, 'rating' => 5]);
        $this->post(route('order.ratings.store'), ['uuid' => $order->uuid, 'rating' => 1])
            ->assertSessionHas('error');

        $this->assertSame(1, OrderRating::count());
        $this->assertSame(5, (int) OrderRating::first()->rating);
    }
}
