<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Integrations\NestEngine\NestEngineClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The nesting result relies entirely on NestEngine: there is no local
 * fallback any more, so without the engine the compute endpoint refuses.
 */
class NestingComputeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);
    }

    /** @test */
    public function the_open_source_edition_does_not_compute_a_nesting(): void
    {
        config(['services.nestengine.enabled' => false]);
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->postJson(route('nesting.compute'), ['include_open' => true])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    /** @test */
    public function an_unreachable_engine_is_reported_instead_of_falling_back(): void
    {
        config([
            'services.nestengine.enabled' => true,
            'services.nestengine.url'     => 'http://nestengine.test',
        ]);
        Http::fake(['nestengine.test/*' => Http::response('', 500)]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('nesting.compute'), ['include_open' => true])
            ->assertStatus(503)
            ->assertJsonStructure(['message']);
    }

    /** @test */
    public function a_reachable_engine_answers_with_the_grouped_pieces(): void
    {
        config([
            'services.nestengine.enabled' => true,
            'services.nestengine.url'     => 'http://nestengine.test',
        ]);
        Http::fake(['nestengine.test/healthz' => Http::response(['status' => 'ok'])]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('nesting.compute'), ['include_open' => true])
            ->assertOk()
            ->assertJsonPath('engine', 'nestengine')
            ->assertJsonPath('services', []);
    }

    /**
     * The delivery date range keeps late lines when only the end is set, and
     * drops (and counts) the undated lines once a bound is given.
     *
     * @test
     */
    public function the_delivery_date_range_narrows_the_lines(): void
    {
        config([
            'services.nestengine.enabled' => true,
            'services.nestengine.url'     => 'http://nestengine.test',
        ]);
        Http::fake(['nestengine.test/healthz' => Http::response(['status' => 'ok'])]);

        $order = Orders::factory()->create(['statu' => 2]);
        $line = fn (?string $date) => OrderLines::factory()->create([
            'orders_id' => $order->id, 'delivery_date' => $date,
        ])->id;
        $late   = $line('2026-11-01');
        $soon   = $line('2026-12-10');
        $later  = $line('2027-06-01');
        $undated = $line(null);

        $lineIds = fn ($response) => collect($response->json('missing_service'))->pluck('line_id')->sort()->values()->all();
        $user = User::factory()->create();

        $all = $this->actingAs($user)->postJson(route('nesting.compute'), [])->assertOk();
        $this->assertSame([$late, $soon, $later, $undated], $lineIds($all));
        $all->assertJsonPath('stats.lines_without_date', 0);

        $horizon = $this->postJson(route('nesting.compute'), ['delivery_to' => '2026-12-31'])->assertOk();
        $this->assertSame([$late, $soon], $lineIds($horizon));
        $horizon->assertJsonPath('stats.lines_without_date', 1);

        $range = $this->postJson(route('nesting.compute'), [
            'delivery_from' => '2026-12-01', 'delivery_to' => '2026-12-31',
        ])->assertOk();
        $this->assertSame([$soon], $lineIds($range));

        $this->postJson(route('nesting.compute'), [
            'delivery_from' => '2026-12-31', 'delivery_to' => '2026-12-01',
        ])->assertStatus(422);
    }

    /**
     * An SVG goes to the engine only if /version lists it: an older engine
     * ignores `svgFile` and would reject the whole group's job.
     *
     * @test
     */
    public function svg_parts_are_sent_only_to_an_engine_that_reads_them(): void
    {
        $client = new NestEngineClient('http://nestengine.test');

        Http::fakeSequence('nestengine.test/version')
            ->push(['inputFormats' => ['dxf', 'svg']])
            ->push(['engine' => 'NestEngine'])
            ->push('', 404);

        $this->assertTrue($client->supportsSvg());
        $this->assertFalse($client->supportsSvg(), 'engine without inputFormats');
        $this->assertFalse($client->supportsSvg(), 'engine without /version');
    }
}
