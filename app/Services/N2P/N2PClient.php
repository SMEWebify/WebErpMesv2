<?php

namespace App\Services\N2P;

use App\Models\Integrations\IntegrationDelivery;
use App\Models\Integrations\IntegrationEndpoint;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class N2PClient
{
    private const TIMEOUT = 15;

    public const PATH_JOBS = '/api/plugin/jobs';
    public const PATH_STOCK_LOTS = '/api/plugin/stock-lots';
    public const PATH_PING = '/api/plugin/ping';
    public const PATH_ERP_JOB_DOCUMENT = '/api/erp/jobs/%d/documents';

    public const EVENT_JOB_PUSHED = 'job.pushed';
    public const EVENT_SHEET_LOT_PUSHED = 'sheet_lot.pushed';
    public const EVENT_PING = '__test__.ping';
    public const EVENT_JOB_DOCUMENT_PUSHED = 'job.document.pushed';

    public function __construct(private readonly IntegrationEndpoint $endpoint)
    {
        if ($this->endpoint->direction !== IntegrationEndpoint::DIRECTION_OUTBOUND) {
            throw new RuntimeException('N2PClient requires an outbound integration endpoint');
        }
        if (empty($this->endpoint->url)) {
            throw new RuntimeException('N2P outbound endpoint has no URL configured');
        }
    }

    public function pushJobs(array $payload): array
    {
        return $this->sendPayload(self::PATH_JOBS, $payload, self::EVENT_JOB_PUSHED);
    }

    public function pushSheetLots(array $payload): array
    {
        return $this->sendPayload(self::PATH_STOCK_LOTS, $payload, self::EVENT_SHEET_LOT_PUSHED);
    }

    /**
     * Attache un fichier brut (SVG typiquement) à un OF côté N2P via l'endpoint
     * ERP HMAC. Contrat imposé par N2P (VerifyErpInboundHmac) :
     *  - corps = fichier brut, PAS de multipart (PHP consomme php://input, la
     *    signature du raw body est donc la seule fiable)
     *  - headers X-N2P-Signature-256 = hex(hmac_sha256(ts . '.' . body, secret))
     *    et X-N2P-Timestamp = unix seconds
     *  - X-N2P-Filename optionnel (sinon N2P génère un nom)
     *
     * Le secret HMAC est celui de l'endpoint outbound n2p. Il DOIT être configuré
     * à l'identique côté N2P dans tenant_settings.erp_integration.hmac_secret,
     * sinon 401.
     */
    public function pushJobDocument(int $jobId, string $body, string $mime, ?string $filename = null): array
    {
        $url = rtrim($this->endpoint->url, '/') . sprintf(self::PATH_ERP_JOB_DOCUMENT, $jobId);
        $timestamp = time();

        $delivery = IntegrationDelivery::create([
            'integration_endpoint_id' => $this->endpoint->id,
            'direction'   => IntegrationDelivery::DIRECTION_OUT,
            'event_id'    => (string) Str::uuid(),
            'event_type'  => self::EVENT_JOB_DOCUMENT_PUSHED,
            // Ne pas persister le binaire (potentiellement des Mo) dans le journal
            // — l'audit garde la trace du push, pas du contenu.
            'payload'     => [
                'job_id'   => $jobId,
                'mime'     => $mime,
                'filename' => $filename,
                'bytes'    => strlen($body),
            ],
            'occurred_at' => now(),
            'error'       => 'sending',
        ]);

        $started = microtime(true);
        $secret  = (string) $this->endpoint->hmac_secret;

        if ($secret === '') {
            $this->recordFailure($delivery, 'hmac_secret_missing', null);
            throw new RuntimeException('N2P outbound endpoint has no HMAC secret configured');
        }

        $headers = [
            'X-N2P-Timestamp'    => (string) $timestamp,
            'X-N2P-Signature-256' => hash_hmac('sha256', $timestamp . '.' . $body, $secret),
        ];

        if ($filename !== null && $filename !== '') {
            $headers['X-N2P-Filename'] = $filename;
        }

        if ($this->endpoint->requiresBearer() && ! empty($this->endpoint->bearer_token)) {
            $headers['Authorization'] = 'Bearer ' . $this->endpoint->bearer_token;
        }

        try {
            $response = Http::acceptJson()
                ->timeout(self::TIMEOUT)
                ->withHeaders($headers)
                ->withOptions(['verify' => (bool) $this->endpoint->verify_ssl])
                ->withBody($body, $mime)
                ->post($url);
        } catch (Throwable $e) {
            $this->recordFailure($delivery, $e->getMessage(), null);
            Log::channel('n2p')->error('N2P document push transport error', [
                'endpoint_id' => $this->endpoint->id,
                'job_id' => $jobId,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $durationMs = (int) ((microtime(true) - $started) * 1000);
        $status = $response->status();
        $responseBody = $response->body();

        Log::channel('n2p')->info('N2P document push request', [
            'endpoint_id' => $this->endpoint->id,
            'delivery_id' => $delivery->id,
            'job_id' => $jobId,
            'status' => $status,
            'bytes' => strlen($body),
        ]);

        if ($response->failed()) {
            $this->recordFailure($delivery, "http_{$status}: {$responseBody}", $status);
            throw new RequestException($response);
        }

        $delivery->markProcessed($status, $responseBody, $durationMs);

        $this->endpoint->update([
            'last_success_at' => now(),
            'last_error_at' => null,
            'last_error_message' => null,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Test de connectivité — POST signé sur /api/plugin/ping.
     * Sert au bouton "Test" de l'admin. Trace une delivery avec event_type=__test__.ping
     * (facile à filtrer / exclure du journal métier).
     */
    public function pushPing(): array
    {
        return $this->sendPayload(self::PATH_PING, [
            'ping'        => true,
            'sent_at'     => now()->toIso8601String(),
            'endpoint_id' => $this->endpoint->id,
        ], self::EVENT_PING);
    }

    /**
     * Envoie un payload signé HMAC vers un path arbitraire de l'endpoint N2P.
     * Journalise chaque tentative dans integration_deliveries (direction=out) et
     * met à jour last_success_at / last_error_at sur l'endpoint.
     */
    public function sendPayload(string $path, array $payload, string $eventType): array
    {
        $url = rtrim($this->endpoint->url, '/') . $path;
        $body = json_encode($payload);
        $timestamp = time();

        $delivery = IntegrationDelivery::create([
            'integration_endpoint_id' => $this->endpoint->id,
            'direction'   => IntegrationDelivery::DIRECTION_OUT,
            'event_id'    => (string) Str::uuid(),
            'event_type'  => $eventType,
            'payload'     => $payload,
            'occurred_at' => now(),
            // Marqueur "in-flight" — sinon les rows en cours d'envoi
            // remontent dans scopePending et faussent le monitoring.
            'error'       => 'sending',
        ]);

        $started = microtime(true);

        try {
            // withBody() envoie le body EXACT qu'on a signé.
            // ->post($url, array) laisse Guzzle ré-encoder l'array (order/escape
            // potentiellement différents) → la signature HMAC échoue côté partenaire.
            $response = Http::acceptJson()
                ->timeout(self::TIMEOUT)
                ->withHeaders($this->headers($timestamp, $body))
                ->withOptions(['verify' => (bool) $this->endpoint->verify_ssl])
                ->withBody($body, 'application/json')
                ->post($url);
        } catch (Throwable $e) {
            $this->recordFailure($delivery, $e->getMessage(), null);
            Log::channel('n2p')->error('N2P push transport error', [
                'endpoint_id' => $this->endpoint->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $durationMs = (int) ((microtime(true) - $started) * 1000);
        $status = $response->status();
        $responseBody = $response->body();

        Log::channel('n2p')->info('N2P push request', [
            'endpoint_id' => $this->endpoint->id,
            'delivery_id' => $delivery->id,
            'url' => $url,
            'status' => $status,
        ]);

        if ($response->failed()) {
            $this->recordFailure($delivery, "http_{$status}: {$responseBody}", $status);
            throw new RequestException($response);
        }

        $delivery->markProcessed($status, $responseBody, $durationMs);

        $this->endpoint->update([
            'last_success_at' => now(),
            'last_error_at' => null,
            'last_error_message' => null,
        ]);

        return $response->json() ?? [];
    }

    private function headers(int $timestamp, string $body): array
    {
        $headers = [];

        if ($this->endpoint->requiresBearer() && ! empty($this->endpoint->bearer_token)) {
            $headers['Authorization'] = 'Bearer ' . $this->endpoint->bearer_token;
        }

        if ($this->endpoint->requiresHmac() && ! empty($this->endpoint->hmac_secret)) {
            $signature = hash_hmac('sha256', $timestamp . '.' . $body, $this->endpoint->hmac_secret);
            $headers[$this->endpoint->timestamp_header ?: 'X-Timestamp'] = (string) $timestamp;
            $headers[$this->endpoint->hmac_header ?: 'X-Signature-256'] = $signature;
        }

        return $headers;
    }

    private function recordFailure(IntegrationDelivery $delivery, string $error, ?int $status): void
    {
        $delivery->fill([
            'error' => $error,
            'http_status' => $status,
        ])->save();

        $this->endpoint->update([
            'last_error_at' => now(),
            'last_error_message' => Str::limit($error, 500),
        ]);
    }
}
