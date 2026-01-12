<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider unauthenticatedApiEndpoints
     */
    public function test_api_endpoints_require_authentication(string $method, string $uri): void
    {
        $response = $this->json($method, $uri);

        $response->assertUnauthorized();
    }

    public static function unauthenticatedApiEndpoints(): array
    {
        return [
            ['GET', '/api/companies'],
            ['GET', '/api/exports/sales-orders'],
            ['GET', '/api/collaboration/whiteboards'],
        ];
    }
}
