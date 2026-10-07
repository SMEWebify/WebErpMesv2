<?php

namespace App\Services\AI;

use App\Models\Integrations\AISetting;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Résout la config runtime des providers IA.
 *
 * Ordre de résolution :
 *   1. Table `ai_settings` (source de vérité)
 *   2. Fallback `config/ai.php` (elle-même lue depuis .env) — rétrocompat.
 *
 * Le fallback évite de casser une instance qui n'a pas encore migré la clé
 * depuis l'écran d'admin. Il disparaîtra le jour où le champ .env sera retiré
 * dans une version majeure.
 *
 * On cache 60s : le tool use enchaîne 2 à 10 appels HTTP par question, on ne
 * hit pas la DB à chaque tour. Le cache est invalidé explicitement par
 * AISettingsController après un save.
 */
class AISettingsResolver
{
    // v2 : la valeur cachée porte désormais le provider (claude | ovh).
    private const CACHE_KEY = 'ai_settings.active.v2';
    private const CACHE_TTL = 60;

    /** Providers OpenAI-compatibles câblés (clé = valeur de ai_settings.provider). */
    public const OPENAI_COMPATIBLE = ['ovh'];

    /**
     * @return array{
     *   provider: string,
     *   api_key: string|null,
     *   model: string|null,
     *   max_tokens: int,
     *   timeout: int,
     *   base_url: string|null,
     *   source: string  // 'db' | 'env'
     * }
     */
    public function claude(): array
    {
        $envConfig = config('ai.providers.claude', []);

        $dbConfig = $this->row();
        if ($dbConfig && $dbConfig['provider'] !== 'claude') {
            $dbConfig = null;
        }

        if ($dbConfig && ! empty($dbConfig['api_key'])) {
            return [
                'provider'   => 'claude',
                'api_key'    => $dbConfig['api_key'],
                'model'      => $dbConfig['model']      ?? ($envConfig['default_model'] ?? null),
                'max_tokens' => $dbConfig['max_tokens'] ?: ($envConfig['max_tokens']    ?? 2048),
                'timeout'    => $dbConfig['timeout']    ?: ($envConfig['timeout']       ?? 30),
                'base_url'   => $dbConfig['base_url']   ?? null,
                'source'     => 'db',
            ];
        }

        return [
            'provider'   => 'claude',
            'api_key'    => $envConfig['api_key']       ?? null,
            'model'      => $envConfig['default_model'] ?? null,
            'max_tokens' => (int) ($envConfig['max_tokens'] ?? 2048),
            'timeout'    => (int) ($envConfig['timeout']    ?? 30),
            'base_url'   => null,
            'source'     => 'env',
        ];
    }

    /**
     * Provider choisi dans l'écran d'admin. Sans ligne en base (ou table pas
     * encore migrée), on reste sur Claude, comme avant l'ajout du choix.
     */
    public function activeProvider(): string
    {
        $row = $this->row();
        if ($row && in_array($row['provider'], self::OPENAI_COMPATIBLE, true)) {
            return $row['provider'];
        }
        return 'claude';
    }

    /** Config du provider actif, quelle que soit sa famille. */
    public function active(): array
    {
        $provider = $this->activeProvider();
        return $provider === 'claude' ? $this->claude() : $this->openAiCompatible($provider);
    }

    /**
     * Config d'un provider à API OpenAI (/chat/completions). Même ordre de
     * résolution que claude() : base si c'est le provider enregistré, sinon .env.
     * La clé peut rester vide : OVH accepte l'accès anonyme (débit limité).
     *
     * @return array{provider: string, api_key: string|null, model: string|null,
     *               max_tokens: int, timeout: int, base_url: string, source: string}
     */
    public function openAiCompatible(string $provider): array
    {
        $envConfig = config("ai.providers.{$provider}", []);
        $row       = $this->row();
        $dbConfig  = ($row && $row['provider'] === $provider) ? $row : null;

        return [
            'provider'   => $provider,
            'api_key'    => ($dbConfig['api_key'] ?? null) ?: ($envConfig['api_key'] ?? null),
            'model'      => ($dbConfig['model'] ?? null) ?: ($envConfig['default_model'] ?? null),
            'max_tokens' => ($dbConfig['max_tokens'] ?? 0) ?: (int) ($envConfig['max_tokens'] ?? 2048),
            'timeout'    => ($dbConfig['timeout'] ?? 0) ?: (int) ($envConfig['timeout'] ?? 60),
            'base_url'   => rtrim(($dbConfig['base_url'] ?? null) ?: ($envConfig['base_url'] ?? ''), '/'),
            'source'     => $dbConfig ? 'db' : 'env',
        ];
    }

    /**
     * Endpoint /messages à appeler. Toujours l'API officielle pour Claude ;
     * on ne surcharge que si base_url a été explicitement renseigné.
     */
    public function claudeEndpoint(): string
    {
        $c = $this->claude();
        if (! empty($c['base_url'])) {
            return rtrim($c['base_url'], '/') . '/v1/messages';
        }
        return config('ai.providers.claude.api_url', 'https://api.anthropic.com/v1/messages');
    }

    public function claudeApiVersion(): string
    {
        return config('ai.providers.claude.api_version', '2023-06-01');
    }

    /** Ligne active de `ai_settings`, cachée 60 s. null si absente ou table pas migrée. */
    private function row(): ?array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, static function () {
            try {
                $row = AISetting::current();
                if (! $row) return null;
                return [
                    'provider'   => $row->provider,
                    'api_key'    => $row->api_key,
                    'model'      => $row->model,
                    'max_tokens' => (int) $row->max_tokens,
                    'timeout'    => (int) $row->timeout_seconds,
                    'base_url'   => $row->base_url,
                ];
            } catch (Throwable) {
                // Table pas encore migrée (déploiement en cours) : fallback env.
                return null;
            }
        });
    }

    /** À appeler depuis l'admin après un save. */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
