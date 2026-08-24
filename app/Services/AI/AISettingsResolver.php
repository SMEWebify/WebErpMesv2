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
    private const CACHE_KEY = 'ai_settings.active';
    private const CACHE_TTL = 60;

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

        $dbConfig = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, static function () {
            try {
                $row = AISetting::current();
                if (! $row || $row->provider !== 'claude') return null;
                return [
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

    /** À appeler depuis l'admin après un save. */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
