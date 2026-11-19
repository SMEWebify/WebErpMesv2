<?php

namespace App\Services\Integrations\SharePoint;

use App\Models\Integrations\SharePointSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Appels Microsoft Graph pour SharePoint : jeton d'application (client_credentials),
 * résolution du site et de la bibliothèque, dossiers, envoi de fichiers.
 *
 * Client HTTP Laravel, pas de SDK. Le secret ne sort jamais d'ici : il n'apparaît ni
 * dans les messages d'erreur ni dans le journal.
 */
class GraphClient
{
    public const LOGIN_URL = 'https://login.microsoftonline.com';
    public const GRAPH_URL = 'https://graph.microsoft.com/v1.0';

    private const EXPIRY_MARGIN = 60;
    private const DEFAULT_TTL = 3000;

    public function __construct(private readonly SharePointSetting $setting) {}

    // ── Jeton ────────────────────────────────────────────────────────────────

    public function token(bool $fresh = false): string
    {
        $key = $this->tokenCacheKey();
        if ($fresh) {
            Cache::forget($key);
        }

        if ($token = Cache::get($key)) {
            return $token;
        }

        try {
            $response = Http::asForm()
                ->connectTimeout(10)
                ->timeout(30)
                ->post(self::LOGIN_URL . '/' . rawurlencode((string) $this->setting->tenant_id) . '/oauth2/v2.0/token', [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->setting->client_id,
                    'client_secret' => $this->setting->client_secret,
                    'scope'         => 'https://graph.microsoft.com/.default',
                ]);
        } catch (ConnectionException $e) {
            throw new SharePointException(__('sharepoint.error.network'), 0);
        }

        if (! $response->successful() || ! is_string($response->json('access_token'))) {
            // AADSTS… : message d'Entra ID, sans le secret.
            $detail = (string) ($response->json('error_description') ?? $response->json('error') ?? '');
            $detail = trim(strtok($detail, "\r\n") ?: '');

            throw new SharePointException(
                __('sharepoint.error.auth') . ($detail !== '' ? ' ' . mb_substr($detail, 0, 300) : ''),
                $response->status() >= 500 ? $response->status() : 401
            );
        }

        $expiresIn = (int) $response->json('expires_in', 0);
        $ttl = $expiresIn > self::EXPIRY_MARGIN ? $expiresIn - self::EXPIRY_MARGIN : self::DEFAULT_TTL;
        Cache::put($key, $response->json('access_token'), $ttl);

        return $response->json('access_token');
    }

    public function forgetToken(): void
    {
        Cache::forget($this->tokenCacheKey());
    }

    private function tokenCacheKey(): string
    {
        // Changer d'application ou de tenant invalide le jeton gardé.
        return 'sharepoint:token:' . sha1($this->setting->tenant_id . '|' . $this->setting->client_id);
    }

    // ── Site et bibliothèque ─────────────────────────────────────────────────

    /**
     * Site d'après son URL, puis bibliothèque d'après son nom (vide = par défaut).
     *
     * @return array{site_id: string, site_name: ?string, drive_id: string, drive_name: ?string, drive_web_url: ?string}
     */
    public function resolveSiteAndDrive(): array
    {
        $parts = parse_url((string) $this->setting->site_url);
        if (! is_array($parts) || empty($parts['host'])) {
            throw new SharePointException(__('sharepoint.error.site_url'), 422);
        }

        $path = trim((string) ($parts['path'] ?? ''), '/');
        $sitePath = $path === '' ? '/sites/' . $parts['host'] : '/sites/' . $parts['host'] . ':/' . $this->encodePath($path);

        $site = $this->json('get', $sitePath);

        $library = trim((string) $this->setting->library);
        if ($library === '') {
            $drive = $this->json('get', '/sites/' . $site['id'] . '/drive');
        } else {
            $drives = $this->json('get', '/sites/' . $site['id'] . '/drives')['value'] ?? [];
            $drive = collect($drives)->first(
                fn (array $d) => mb_strtolower((string) ($d['name'] ?? '')) === mb_strtolower($library)
            );

            if (! $drive) {
                throw new SharePointException(__('sharepoint.error.library', [
                    'library' => $library,
                    'available' => collect($drives)->pluck('name')->filter()->implode(', ') ?: '—',
                ]), 404);
            }
        }

        return [
            'site_id'       => (string) $site['id'],
            'site_name'     => $site['displayName'] ?? $site['name'] ?? null,
            'drive_id'      => (string) $drive['id'],
            'drive_name'    => $drive['name'] ?? null,
            'drive_web_url' => $drive['webUrl'] ?? null,
        ];
    }

    // ── Dossiers ─────────────────────────────────────────────────────────────

    /**
     * Sous-dossier `$name` de `$parentId` ('root' = racine de la bibliothèque),
     * créé s'il n'existe pas.
     *
     * @return array{id: string, webUrl: ?string}
     */
    public function ensureFolder(string $parentId, string $name): array
    {
        try {
            $item = $this->json('post', $this->itemPath($parentId) . '/children', [
                'name' => $name,
                'folder' => new \stdClass(),
                '@microsoft.graph.conflictBehavior' => 'fail',
            ]);
        } catch (SharePointException $e) {
            if ($e->status !== 409) {
                throw $e;
            }
            // Déjà là (créé par quelqu'un d'autre, ou par une recopie précédente).
            $item = $this->json('get', $this->itemPath($parentId) . ':/' . rawurlencode($name));

            if (! isset($item['folder'])) {
                throw new SharePointException(__('sharepoint.error.not_a_folder', ['name' => $name]), 409);
            }
        }

        return ['id' => (string) $item['id'], 'webUrl' => $item['webUrl'] ?? null];
    }

    // ── Fichiers ─────────────────────────────────────────────────────────────

    /**
     * Envoie un fichier : remplace le contenu de `$itemId` s'il est donné (SharePoint en
     * garde l'ancienne version), sinon crée `$name` dans `$folderId` (renommé en cas de
     * conflit, jamais écrasé).
     *
     * @param  resource  $stream
     * @return array{id: string, name: string, webUrl: ?string}
     */
    public function upload(string $folderId, string $name, $stream, int $size, ?string $itemId = null): array
    {
        $target = $itemId !== null
            ? $this->itemPath($itemId)
            : $this->itemPath($folderId) . ':/' . rawurlencode($name) . ':';

        $item = $size <= (int) config('sharepoint.simple_upload_max_bytes')
            ? $this->simpleUpload($target, $stream, $itemId === null)
            : $this->sessionUpload($target, $stream, $size, $itemId === null);

        return ['id' => (string) $item['id'], 'name' => (string) ($item['name'] ?? $name), 'webUrl' => $item['webUrl'] ?? null];
    }

    /** @param resource $stream */
    private function simpleUpload(string $target, $stream, bool $isNew): array
    {
        $contents = stream_get_contents($stream);
        $url = $target . '/content' . ($isNew ? '?@microsoft.graph.conflictBehavior=rename' : '');

        return $this->decode($this->send(fn (PendingRequest $http) => $http
            ->withBody($contents === false ? '' : $contents, 'application/octet-stream')
            ->put(self::GRAPH_URL . $url)));
    }

    /** @param resource $stream */
    private function sessionUpload(string $target, $stream, int $size, bool $isNew): array
    {
        $session = $this->json('post', $target . '/createUploadSession', [
            'item' => ['@microsoft.graph.conflictBehavior' => $isNew ? 'rename' : 'replace'],
        ]);

        $uploadUrl = (string) ($session['uploadUrl'] ?? '');
        if ($uploadUrl === '') {
            throw new SharePointException(__('sharepoint.error.upload_session'), 502);
        }

        $chunk = (int) config('sharepoint.chunk_bytes');
        $offset = 0;
        $response = null;

        while ($offset < $size) {
            $bytes = stream_get_contents($stream, $chunk);
            if ($bytes === false || $bytes === '') {
                break;
            }
            $end = $offset + strlen($bytes) - 1;

            // L'URL de session porte sa propre autorisation : pas de jeton ici.
            try {
                $response = Http::withHeaders(['Content-Range' => "bytes {$offset}-{$end}/{$size}"])
                    ->connectTimeout(10)
                    ->timeout((int) config('sharepoint.timeout'))
                    ->withBody($bytes, 'application/octet-stream')
                    ->put($uploadUrl);
            } catch (ConnectionException $e) {
                throw new SharePointException(__('sharepoint.error.network'), 0);
            }

            if (! $response->successful()) {
                throw $this->failure($response);
            }

            $offset = $end + 1;
        }

        if (! $response || ! isset($response->json()['id'])) {
            throw new SharePointException(__('sharepoint.error.upload_session'), 502);
        }

        return $response->json();
    }

    // ── Bas niveau ───────────────────────────────────────────────────────────

    private function itemPath(string $itemId): string
    {
        $drive = '/drives/' . $this->setting->drive_id;

        return $itemId === 'root' ? $drive . '/root' : $drive . '/items/' . rawurlencode($itemId);
    }

    private function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    private function json(string $method, string $path, array $body = []): array
    {
        return $this->decode($this->send(fn (PendingRequest $http) => $method === 'get'
            ? $http->get(self::GRAPH_URL . $path)
            : $http->asJson()->{$method}(self::GRAPH_URL . $path, $body)));
    }

    /**
     * @param  \Closure(PendingRequest): Response  $call
     */
    private function send(\Closure $call): Response
    {
        $request = fn (string $token) => Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout((int) config('sharepoint.timeout'));

        try {
            $response = $call($request($this->token()));
            if ($response->status() === 401) {
                $response = $call($request($this->token(true)));
            }
        } catch (ConnectionException $e) {
            throw new SharePointException(__('sharepoint.error.network'), 0);
        }

        return $response;
    }

    private function decode(Response $response): array
    {
        if (! $response->successful()) {
            throw $this->failure($response);
        }

        return (array) $response->json();
    }

    private function failure(Response $response): SharePointException
    {
        $status = $response->status();
        $detail = (string) ($response->json('error.message') ?? '');
        $retryAfter = $response->header('Retry-After');

        $message = match (true) {
            $status === 403 => __('sharepoint.error.forbidden'),
            $status === 404 => __('sharepoint.error.not_found'),
            $status === 429 => __('sharepoint.error.throttled'),
            default => __('sharepoint.error.http', ['status' => $status]),
        };

        return new SharePointException(
            $message . ($detail !== '' ? ' (' . mb_substr($detail, 0, 200) . ')' : ''),
            $status,
            is_numeric($retryAfter) ? (int) $retryAfter : null,
        );
    }
}
