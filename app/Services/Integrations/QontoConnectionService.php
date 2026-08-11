<?php

namespace App\Services\Integrations;

use App\Models\Integrations\QontoConnection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/**
 * Centralise la gestion du token OAuth Qonto (récupération + refresh automatique).
 */
class QontoConnectionService
{
    /**
     * Retourne un access_token valide (refresh si expiré).
     */
    public function getValidToken(int $tenantId): string
    {
        return Crypt::decryptString($this->getValidConnection($tenantId)->access_token);
    }

    /**
     * Retourne la connexion Qonto valide (refresh si expiré).
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getValidConnection(int $tenantId): QontoConnection
    {
        $connection = QontoConnection::where('tenant_id', $tenantId)->firstOrFail();

        if (! $connection->access_token_expires_at || $connection->access_token_expires_at->isFuture()) {
            return $connection;
        }

        $refresh = Http::asForm()->post(
            rtrim(config('services.qonto.oauth_base_url', 'https://oauth.qonto.com'), '/').'/oauth2/token',
            [
                'grant_type'    => 'refresh_token',
                'client_id'     => config('services.qonto.client_id'),
                'client_secret' => config('services.qonto.client_secret'),
                'refresh_token' => Crypt::decryptString($connection->refresh_token),
            ]
        )->throw()->json();

        $connection->forceFill([
            'access_token'            => Crypt::encryptString($refresh['access_token']),
            'refresh_token'           => Crypt::encryptString($refresh['refresh_token'] ?? Crypt::decryptString($connection->refresh_token)),
            'access_token_expires_at' => now()->addSeconds((int) ($refresh['expires_in'] ?? 3600)),
            'scope'                   => $refresh['scope'] ?? $connection->scope,
        ])->save();

        return $connection->fresh();
    }

    /**
     * IBAN du compte Qonto à faire figurer sur les factures émises
     * (`payment_methods.iban`, obligatoire à la création d'une client_invoice).
     *
     * Résolu une fois puis mis en cache sur la connexion. Nécessite le scope
     * `organization.read`.
     *
     * @throws \RuntimeException si aucun compte bancaire n'est exposé par Qonto
     */
    public function getInvoicingIban(int $tenantId): string
    {
        $connection = $this->getValidConnection($tenantId);

        if ($connection->iban) {
            return $connection->iban;
        }

        $organization = Http::withToken(Crypt::decryptString($connection->access_token))
            ->get(rtrim(config('services.qonto.api_base_url', 'https://thirdparty.qonto.com/v2'), '/').'/organization')
            ->throw()
            ->json('organization', []);

        $accounts = $organization['bank_accounts'] ?? [];
        $main     = collect($accounts)->firstWhere('main', true) ?? ($accounts[0] ?? null);
        $iban     = $main['iban'] ?? null;

        if (! $iban) {
            throw new \RuntimeException(
                'Aucun IBAN Qonto disponible : vérifiez que le scope organization.read est accordé.'
            );
        }

        // iban et organization_slug sont castés (encrypted / string) : on assigne en clair.
        $connection->forceFill([
            'iban'              => $iban,
            'organization_slug' => $organization['slug'] ?? $connection->organization_slug,
        ])->save();

        return $iban;
    }

    /**
     * Vérifie si l'intégration Qonto est activée (credentials présents).
     */
    public static function isEnabled(): bool
    {
        return (string) config('services.qonto.client_id', '') !== ''
            && (string) config('services.qonto.client_secret', '') !== '';
    }
}
