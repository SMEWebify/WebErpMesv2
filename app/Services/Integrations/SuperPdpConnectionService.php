<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Obtention et mise en cache de l'access_token SUPER PDP (OAuth 2.1).
 *
 * Grant type `client_credentials` : un couple client_id/client_secret créé dans
 * l'interface SUPER PDP donne accès au compte de l'entreprise à laquelle il est
 * rattaché. C'est le mode adapté à un ERP mono-société installé chez le client —
 * pas de consentement à collecter, pas de redirection.
 *
 * (Le grant `authorization_code` du même serveur servirait un déploiement SaaS
 * multi-clients, où chaque utilisateur autorise WEM sur SON compte SUPER PDP.
 * Il faudrait alors stocker un refresh_token par tenant plutôt qu'un secret
 * unique en configuration.)
 *
 * L'access_token vit 30 minutes côté SUPER PDP ; on le met en cache un peu moins
 * longtemps, et on sait le rafraîchir de force si un 401 survient malgré tout.
 */
class SuperPdpConnectionService
{
    /** Marge de sécurité retirée à la durée de vie annoncée par le serveur. */
    private const EXPIRY_MARGIN = 60;

    /** Durée de vie repli si le serveur n'annonce pas d'expires_in. */
    private const DEFAULT_TTL = 1500; // 25 min

    public static function isEnabled(): bool
    {
        return filled(config('services.superpdp.client_id'))
            && filled(config('services.superpdp.client_secret'));
    }

    /**
     * @param bool $fresh ignore le cache et redemande un token au serveur
     *
     * @throws \RuntimeException si l'intégration n'est pas configurée
     * @throws \Illuminate\Http\Client\RequestException si le serveur refuse les identifiants
     */
    public function getValidToken(bool $fresh = false): string
    {
        if (! self::isEnabled()) {
            throw new \RuntimeException(
                'SUPER PDP n\'est pas configuré : renseignez SUPERPDP_CLIENT_ID et SUPERPDP_CLIENT_SECRET.'
            );
        }

        $key = $this->cacheKey();

        if ($fresh) {
            Cache::forget($key);
        }

        if ($token = Cache::get($key)) {
            return $token;
        }

        [$token, $ttl] = $this->requestToken();

        Cache::put($key, $token, $ttl);

        return $token;
    }

    /** Invalide le token en cache (à appeler sur un 401). */
    public function forgetToken(): void
    {
        Cache::forget($this->cacheKey());
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.superpdp.base_url', 'https://api.superpdp.tech'), '/');
    }

    /**
     * @return array{0: string, 1: int} le token et sa durée de mise en cache
     */
    private function requestToken(): array
    {
        $response = Http::asForm()
            ->post($this->baseUrl() . '/oauth2/token', [
                'grant_type'    => 'client_credentials',
                'client_id'     => config('services.superpdp.client_id'),
                'client_secret' => config('services.superpdp.client_secret'),
            ])
            ->throw();

        $token = (string) $response->json('access_token', '');

        if ($token === '') {
            throw new \RuntimeException('SUPER PDP a répondu sans access_token.');
        }

        $expiresIn = (int) $response->json('expires_in', 0);
        $ttl       = $expiresIn > self::EXPIRY_MARGIN
            ? $expiresIn - self::EXPIRY_MARGIN
            : self::DEFAULT_TTL;

        return [$token, $ttl];
    }

    /**
     * La clé dépend du client_id : changer d'identifiants (ou basculer du bac à
     * sable vers la production) invalide mécaniquement le token en cache.
     */
    private function cacheKey(): string
    {
        return 'superpdp:token:' . sha1((string) config('services.superpdp.client_id'));
    }
}
