<?php

namespace App\Services\N2P;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N2PClient
{
    private const TIMEOUT = 15;

    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $token = null,
        private readonly bool $verifySsl = true
    )
    {
    }

    public function pushJobs(array $payload): array
    {
        $url = rtrim($this->baseUrl, '/') . '/api/plugin/jobs';

        $response = Http::acceptJson()
            ->contentType('application/json')
            ->timeout(self::TIMEOUT)
            ->withHeaders($this->authorizationHeader())
            ->withOptions(['verify' => $this->verifySsl])
            ->post($url, $payload);

        Log::channel('n2p')->info('N2P push request', [
            'url' => $url,
            'payload' => $payload,
            'status' => $response->status(),
        ]);

        if ($response->failed()) {
            $body = $response->body();
            Log::channel('n2p')->error('N2P push failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $body,
            ]);

            throw new RequestException($response);
        }

        return $response->json();
    }

    private function authorizationHeader(): array
    {
        if (empty($this->token)) {
            return [];
        }

        return ['Authorization' => 'Bearer ' . $this->token];
    }
}
