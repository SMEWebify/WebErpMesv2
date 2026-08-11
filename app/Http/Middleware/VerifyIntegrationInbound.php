<?php

namespace App\Http\Middleware;

use App\Models\Integrations\IntegrationEndpoint;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyIntegrationInbound
{
    public const REQUEST_ATTR = 'integration_endpoint';

    public function handle(Request $request, Closure $next): Response
    {
        $systemCode = $request->route('system_code');

        if (! is_string($systemCode) || $systemCode === '') {
            return $this->reject(404, 'unknown_endpoint');
        }

        $endpoint = IntegrationEndpoint::query()
            ->forSystem($systemCode)
            ->inbound()
            ->first();

        if (! $endpoint) {
            return $this->reject(404, 'unknown_endpoint');
        }

        if (! $endpoint->is_active) {
            return $this->reject(403, 'endpoint_disabled');
        }

        if ($endpoint->requiresBearer() && ! $this->checkBearer($request, $endpoint)) {
            return $this->reject(401, 'bearer_mismatch', $endpoint);
        }

        if ($endpoint->requiresHmac() && ! $this->checkHmac($request, $endpoint)) {
            return $this->reject(401, 'hmac_mismatch', $endpoint);
        }

        $request->attributes->set(self::REQUEST_ATTR, $endpoint);

        return $next($request);
    }

    private function checkBearer(Request $request, IntegrationEndpoint $endpoint): bool
    {
        $expected = $endpoint->bearer_token;
        if ($expected === null || $expected === '') {
            return false;
        }

        $provided = $request->bearerToken();
        if ($provided === null) {
            return false;
        }

        return hash_equals($expected, $provided);
    }

    private function checkHmac(Request $request, IntegrationEndpoint $endpoint): bool
    {
        $secret = $endpoint->hmac_secret;
        if ($secret === null || $secret === '') {
            return false;
        }

        $timestampHeader = $endpoint->timestamp_header ?: 'X-Timestamp';
        $signatureHeader = $endpoint->hmac_header ?: 'X-Signature-256';

        $timestamp = $request->header($timestampHeader);
        $signature = $request->header($signatureHeader);

        if (! is_numeric($timestamp) || ! is_string($signature) || $signature === '') {
            return false;
        }

        $timestamp = (int) $timestamp;
        $tolerance = (int) ($endpoint->timestamp_tolerance_s ?: 300);
        if (abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $signed = $timestamp . '.' . $request->getContent();
        $expected = hash_hmac('sha256', $signed, $secret);

        return hash_equals($expected, $signature);
    }

    private function reject(int $status, string $reason, ?IntegrationEndpoint $endpoint = null): JsonResponse
    {
        Log::warning('Integration inbound rejected', [
            'reason' => $reason,
            'status' => $status,
            'endpoint_id' => $endpoint?->id,
            'system_code' => $endpoint?->system_code,
        ]);

        return response()->json(['error' => $reason], $status);
    }
}
