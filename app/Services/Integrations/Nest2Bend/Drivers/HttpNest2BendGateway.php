<?php

namespace App\Services\Integrations\Nest2Bend\Drivers;

use App\Models\Integrations\Nest2BendSetting;
use App\Services\Integrations\Nest2Bend\Contracts\Nest2BendGateway;
use App\Services\Integrations\Nest2Bend\Data\AccountState;
use App\Services\Integrations\Nest2Bend\Data\BendingResult;
use App\Services\Integrations\Nest2Bend\Data\EmbedSession;
use App\Services\Integrations\Nest2Bend\Data\SetupSheet;
use App\Services\Integrations\Nest2Bend\Exceptions\Nest2BendException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP Laravel de l'API NEST2BEND (pas de SDK).
 *
 * Rien de ce qui part n'est journalisé : ni la clé (en-tête Authorization), ni le corps
 * (le STEP est le savoir-faire du client). Les erreurs ne portent que le statut.
 */
class HttpNest2BendGateway implements Nest2BendGateway
{
    /** Lecture de compte et de résultat : réponses immédiates. */
    private const TIMEOUT_READ = 20;

    /** Création de session : NEST2BEND lit le STEP avant de répondre (≥ 60 s demandé). */
    private const TIMEOUT_EMBED = 90;

    /** Fiche de réglage : le PDF est mis en page à chaque appel (60 s conseillé). */
    private const TIMEOUT_SHEET = 60;

    /** Une fiche de réglage tient en quelques pages : au-delà, la réponse n'est pas rangée. */
    private const MAX_SHEET_BYTES = 25 * 1024 * 1024;

    public function __construct(
        private readonly string $baseUrl,
        #[\SensitiveParameter] private readonly string $apiKey,
    ) {}

    public function account(): AccountState
    {
        $response = $this->send(fn () => $this->request(self::TIMEOUT_READ)->get('compte'));

        return AccountState::fromArray($this->json($response));
    }

    public function createEmbedSession(array $payload): EmbedSession
    {
        $response = $this->send(fn () => $this->request(self::TIMEOUT_EMBED)->post('embed-sessions', $payload));

        $body = $this->json($response);
        $url = (string) ($body['url'] ?? '');
        $sessionId = (string) ($body['session_id'] ?? '');
        if ($response->status() !== 201 || $url === '' || $sessionId === '') {
            throw $this->unexpected($response->status());
        }

        $remarques = is_array($body['analyse']['remarques'] ?? null) ? $body['analyse']['remarques'] : [];

        return new EmbedSession(
            sessionId: $sessionId,
            url: $url,
            expiresIn: (int) ($body['expires_in'] ?? 60),
            remarques: array_values(array_map('strval', array_filter($remarques, 'is_scalar'))),
        );
    }

    public function result(string $resultId): BendingResult
    {
        $response = $this->send(fn () => $this->request(self::TIMEOUT_READ)->get('results/' . rawurlencode($resultId)));

        $result = BendingResult::fromArray($this->json($response));
        if ($result->resultId === '' || $result->sessionId === '') {
            throw $this->unexpected($response->status());
        }

        return $result;
    }

    public function setupSheet(string $url): SetupSheet
    {
        // La clé ne part que vers l'origine configurée : une URL `fiche` pointant ailleurs
        // est refusée sans être appelée.
        $origin = Nest2BendSetting::originOf($this->baseUrl);
        if ($origin === null || Nest2BendSetting::originOf($url) !== $origin) {
            throw $this->unexpected(null);
        }

        $response = $this->send(fn () => Http::withToken($this->apiKey)
            ->accept('application/pdf')
            ->connectTimeout(10)
            ->timeout(self::TIMEOUT_SHEET)
            ->get($url));

        $contents = $response->body();
        if (! str_starts_with($contents, '%PDF-') || strlen($contents) > self::MAX_SHEET_BYTES) {
            throw $this->unexpected($response->status());
        }

        return new SetupSheet($contents, SetupSheet::fileNameFrom($response->header('Content-Disposition'), 'fiche-reglage-nest2bend.pdf'));
    }

    private function request(int $timeout): PendingRequest
    {
        return Http::baseUrl(rtrim($this->baseUrl, '/') . '/api/v1/')
            ->withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout($timeout);
    }

    /**
     * @param  callable(): Response  $call
     */
    private function send(callable $call): Response
    {
        try {
            $response = $call();
        } catch (ConnectionException) {
            Log::warning('NEST2BEND injoignable');

            throw new Nest2BendException(Nest2BendException::UNAVAILABLE, __('nest2bend.errors.unavailable'));
        }

        if ($response->successful()) {
            return $response;
        }

        $status = $response->status();
        $message = is_string($response->json('message')) ? $response->json('message') : null;

        throw match (true) {
            $status === 401 => new Nest2BendException(Nest2BendException::UNAUTHORIZED, __('nest2bend.errors.unauthorized'), $status),
            // Le 403 porte `droits` (format de /compte) : l'ERP met la carte à jour sans rappeler /compte.
            $status === 403 => new Nest2BendException(
                Nest2BendException::INACTIVE,
                __('nest2bend.errors.inactive'),
                $status,
                account: is_array($response->json('droits')) ? AccountState::fromArray(['droits' => $response->json('droits')]) : null,
            ),
            $status === 429 => new Nest2BendException(Nest2BendException::RATE_LIMITED, __('nest2bend.errors.rate_limited'), $status),
            $status === 404 => new Nest2BendException(Nest2BendException::NOT_FOUND, __('nest2bend.errors.not_found'), $status),
            // Message de NEST2BEND, déjà traduit selon `langue` (ex. « le fichier contient 3 pièces »).
            $status === 422 => new Nest2BendException(
                Nest2BendException::INVALID,
                $message ?: __('nest2bend.errors.invalid'),
                $status,
                is_array($response->json('errors')) ? $response->json('errors') : [],
            ),
            $status >= 500 => $this->unavailable($status),
            default => $this->unexpected($status),
        };
    }

    private function json(Response $response): array
    {
        $body = $response->json();

        if (! is_array($body)) {
            throw $this->unexpected($response->status());
        }

        return $body;
    }

    private function unavailable(int $status): Nest2BendException
    {
        Log::warning('NEST2BEND indisponible', ['status' => $status]);

        return new Nest2BendException(Nest2BendException::UNAVAILABLE, __('nest2bend.errors.unavailable'), $status);
    }

    private function unexpected(?int $status): Nest2BendException
    {
        Log::warning('NEST2BEND : réponse hors contrat', ['status' => $status]);

        return new Nest2BendException(Nest2BendException::UNEXPECTED, __('nest2bend.errors.unexpected'), $status);
    }
}
