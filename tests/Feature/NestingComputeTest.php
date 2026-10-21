<?php

namespace Tests\Feature;

use App\Models\User;
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
