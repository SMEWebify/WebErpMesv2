<?php

namespace Tests\Feature;

use App\Services\N2P\N2PClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class N2PClientTest extends TestCase
{
    public function test_push_jobs_success(): void
    {
        Http::fake([
            'https://n2p.test/api/plugin/jobs' => Http::response(['ok' => true], 200),
        ]);

        $client = new N2PClient('https://n2p.test', 'token');

        $response = $client->pushJobs(['jobs' => []]);

        $this->assertSame(['ok' => true], $response);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://n2p.test/api/plugin/jobs'
                && $request->hasHeader('Authorization', 'Bearer token');
        });
    }

    public function test_push_jobs_throws_on_failure(): void
    {
        $this->expectException(RequestException::class);

        Http::fake([
            'https://n2p.test/api/plugin/jobs' => Http::response(['error' => 'fail'], 500),
        ]);

        $client = new N2PClient('https://n2p.test', null);
        $client->pushJobs(['jobs' => []]);
    }
}
