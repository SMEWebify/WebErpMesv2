<?php

namespace App\Services\Integrations\NestEngine;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin HTTP client around the NestEngine REST API (FastAPI service running
 * locally on the ERP host). The engine calculates true-shape nesting jobs
 * asynchronously — this client only creates jobs and proxies their status
 * and files; polling until "done" is the caller's job.
 *
 * Contract documented in the NestEngine README. We use v1 (mono-format) since
 * each Laravel-side group already carries a single sheet size.
 */
class NestEngineClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeout = 120,
    ) {}

    public static function fromConfig(): self
    {
        $url = config('services.nestengine.url');
        if (!$url) {
            throw new RuntimeException('NestEngine URL non configurée (NESTENGINE_URL).');
        }
        return new self(rtrim($url, '/'), (int) config('services.nestengine.timeout', 120));
    }

    /**
     * Create a v1 nesting job. Parts reference DXFs by path relative to the
     * engine's NEST_INPUTS_DIR — the caller must have written them there
     * before calling this method.
     *
     * @param array<int, array{id: string, qty: int, dxfFile: string, name?: string, color?: string}> $parts
     * @return string jobId
     */
    public function createJob(float $binW, float $binH, array $parts, array $config = [], int $maxSheets = 50): string
    {
        $payload = [
            'binW'      => $binW,
            'binH'      => $binH,
            'config'    => $config,
            'parts'     => $parts,
            'maxSheets' => $maxSheets,
        ];

        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->post($this->baseUrl.'/jobs', $payload);

        if (!$response->successful()) {
            throw new RuntimeException(
                "NestEngine createJob failed (HTTP {$response->status()}): {$response->body()}"
            );
        }

        $jobId = $response->json('jobId');
        if (!is_string($jobId) || $jobId === '') {
            throw new RuntimeException('NestEngine: réponse createJob sans jobId.');
        }

        return $jobId;
    }

    /**
     * Retrieve the current job meta. Status is one of pending / running /
     * done / error. When done, meta['result'] and meta['files'] are set.
     *
     * @return array<string, mixed>
     */
    public function getJob(string $jobId): array
    {
        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->get($this->baseUrl."/jobs/$jobId");

        if ($response->status() === 404) {
            throw new RuntimeException("NestEngine: job $jobId introuvable.");
        }
        if (!$response->successful()) {
            throw new RuntimeException(
                "NestEngine getJob failed (HTTP {$response->status()}): {$response->body()}"
            );
        }

        return $response->json();
    }

    /**
     * Fetch the SVG preview of sheet N (1-based). Returns the raw file body.
     */
    public function fetchSvg(string $jobId, int $sheet): string
    {
        return $this->fetchFile($jobId, $sheet, 'svg');
    }

    /**
     * Fetch the DXF of sheet N (1-based). Returns the raw file body.
     */
    public function fetchDxf(string $jobId, int $sheet): string
    {
        return $this->fetchFile($jobId, $sheet, 'dxf');
    }

    private function fetchFile(string $jobId, int $sheet, string $ext): string
    {
        $response = Http::timeout($this->timeout)
            ->get($this->baseUrl."/jobs/$jobId/$ext/$sheet");

        if (!$response->successful()) {
            throw new RuntimeException(
                "NestEngine fetch $ext#$sheet failed (HTTP {$response->status()})"
            );
        }
        return $response->body();
    }

    /**
     * Poll /healthz — used at startup to decide if the toggle is actionable.
     */
    public function isReachable(): bool
    {
        try {
            $response = Http::timeout(3)->get($this->baseUrl.'/healthz');
            return $response->successful();
        } catch (ConnectionException) {
            return false;
        }
    }
}
