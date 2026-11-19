<?php

namespace App\Services\Configurators;

use App\Models\Integrations\ExternalConfigurator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Appels de l'ERP vers un configurateur externe (contrat v1,
 * docs/conception/configurateurs-externes.md § 4). Pas de SDK : client HTTP Laravel,
 * clé en Bearer. Toute erreur sort en ConfiguratorException au message traduit.
 */
class ConfiguratorClient
{
    public function __construct(private readonly ExternalConfigurator $configurator) {}

    /** @return array{name?: string, version?: string, contract?: string} */
    public function ping(): array
    {
        return $this->json($this->send(fn () => $this->request()->get('ping')));
    }

    /**
     * @return array{session_id: string, url: string, expires_at?: string}
     */
    public function openSession(array $payload): array
    {
        $data = $this->json($this->send(fn () => $this->request()->post('sessions', $payload)));

        if (! is_string($data['session_id'] ?? null) || ! is_string($data['url'] ?? null)) {
            throw new ConfiguratorException(__('configurators.errors.unexpected'));
        }

        // Une URL d'une autre origine que celle réglée est refusée (iframe, redirection).
        $origin = $this->configurator->origin();
        $parts = parse_url($data['url']);
        $urlOrigin = isset($parts['scheme'], $parts['host'])
            ? $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '')
            : null;
        if ($origin === null || $urlOrigin !== $origin) {
            throw new ConfiguratorException(__('configurators.errors.foreign_url'));
        }

        return $data;
    }

    public function result(string $resultId): array
    {
        // 409 = pas encore prêt : quelques essais rapprochés avant d'abandonner.
        for ($attempt = 1; ; $attempt++) {
            $response = $this->send(fn () => $this->request()->get('results/' . rawurlencode($resultId)), allow409: true);
            if ($response->status() !== 409 || $attempt >= 3) {
                break;
            }
            usleep(1_000_000);
        }

        if ($response->status() === 409) {
            throw new ConfiguratorException(__('configurators.errors.not_ready'));
        }

        return $this->json($response);
    }

    /**
     * @return array{contents: string, mime: ?string}
     */
    public function file(string $resultId, string $fileId): array
    {
        $response = $this->send(fn () => $this->request((int) config('configurators.file_timeout', 30))
            ->get('results/' . rawurlencode($resultId) . '/files/' . rawurlencode($fileId)));

        return ['contents' => $response->body(), 'mime' => $response->header('Content-Type') ?: null];
    }

    private function request(?int $timeout = null): PendingRequest
    {
        return Http::baseUrl(rtrim($this->configurator->base_url, '/') . '/api/v1/')
            ->withToken((string) $this->configurator->api_key)
            ->acceptJson()
            ->timeout($timeout ?? (int) config('configurators.timeout', 10));
    }

    private function send(callable $call, bool $allow409 = false): Response
    {
        try {
            /** @var Response $response */
            $response = $call();
        } catch (ConnectionException) {
            throw new ConfiguratorException(__('configurators.errors.unavailable'));
        }

        if ($response->successful() || ($allow409 && $response->status() === 409)) {
            return $response;
        }

        $message = match (true) {
            $response->status() === 401, $response->status() === 403 => __('configurators.errors.unauthorized'),
            $response->status() === 404 => __('configurators.errors.not_found'),
            $response->status() === 422 => (string) ($response->json('message') ?: __('configurators.errors.unexpected')),
            $response->status() === 429 => __('configurators.errors.rate_limited'),
            default => __('configurators.errors.unavailable'),
        };

        throw new ConfiguratorException(mb_substr($message, 0, 500), $response->status());
    }

    private function json(Response $response): array
    {
        $data = $response->json();
        if (! is_array($data)) {
            throw new ConfiguratorException(__('configurators.errors.unexpected'));
        }

        return $data;
    }
}
