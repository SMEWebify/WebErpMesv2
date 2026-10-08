<?php

namespace App\Services\Integrations\Signature\Drivers;

use App\Models\Integrations\ESignatureSetting;
use App\Models\Workflow\QuoteSignature;
use App\Services\Integrations\Signature\Contracts\SignatureGateway;
use App\Services\Integrations\Signature\Data\EnvelopeState;
use App\Services\Integrations\Signature\Data\SignatureRequest;
use App\Services\Integrations\Signature\Exceptions\SignatureException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Driver DocuSign eSignature (API REST v2.1).
 *
 * Authentification JWT Grant : WEM signe une assertion RS256 avec la clé privée
 * de l'application d'intégration, au nom d'un utilisateur DocuSign (« utilisateur
 * API »). Aucun SDK : la signature passe par openssl, les appels par le client
 * HTTP de Laravel. Le premier appel exige le consentement unique de cet
 * utilisateur, d'où consentUrl().
 *
 * Le bac à sable et la production ne diffèrent que par le serveur OAuth ; le
 * serveur d'API propre au compte (na3, eu…) est lu sur /oauth/userinfo.
 */
class DocuSignGateway implements SignatureGateway
{
    /**
     * Marqueurs posés en texte blanc sur la page « Bon pour accord » : DocuSign y
     * accroche les champs à remplir. Voir print/esignature-acceptance.blade.php.
     */
    public const ANCHOR_SIGN = '/wem_sign/';
    public const ANCHOR_NAME = '/wem_name/';
    public const ANCHOR_DATE = '/wem_date/';

    private const SCOPES = 'signature impersonation';

    public function __construct(private readonly ESignatureSetting $setting) {}

    public function key(): string
    {
        return 'docusign';
    }

    public function oauthHost(): string
    {
        return $this->setting->environment === ESignatureSetting::ENV_PRODUCTION
            ? 'account.docusign.com'
            : 'account-d.docusign.com';
    }

    /**
     * Lien à ouvrir une fois, connecté avec l'utilisateur API, pour autoriser
     * l'application à agir en son nom. L'URL de retour doit être déclarée dans
     * les « Redirect URIs » de l'application DocuSign.
     */
    public function consentUrl(string $redirectUri): string
    {
        return 'https://' . $this->oauthHost() . '/oauth/auth?' . http_build_query([
            'response_type' => 'code',
            'scope'         => self::SCOPES,
            'client_id'     => $this->setting->integration_key,
            'redirect_uri'  => $redirectUri,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function send(SignatureRequest $request): string
    {
        $documents = [];
        foreach (array_values($request->documents) as $index => $document) {
            $documents[] = [
                'documentId'     => (string) ($index + 1),
                'name'           => $document['name'],
                'fileExtension'  => 'pdf',
                'documentBase64' => base64_encode($document['content']),
            ];
        }

        $signer = [
            'email'        => $request->signerEmail,
            'name'         => $request->signerName,
            'recipientId'  => '1',
            'routingOrder' => '1',
            'tabs'         => [
                'signHereTabs'   => [$this->anchor(self::ANCHOR_SIGN)],
                'fullNameTabs'   => [$this->anchor(self::ANCHOR_NAME)],
                'dateSignedTabs' => [$this->anchor(self::ANCHOR_DATE)],
            ],
        ];

        if ($request->clientUserId !== null) {
            // Signataire intégré : DocuSign n'envoie pas d'e-mail, la session de
            // signature s'ouvre depuis la page publique du devis.
            $signer['clientUserId'] = $request->clientUserId;
        }

        $body = [
            'emailSubject' => mb_substr($request->subject, 0, 100),
            'documents'    => $documents,
            'recipients'   => ['signers' => [$signer]],
            'status'       => 'sent',
        ];

        if ($request->webhookUrl !== null) {
            $body['eventNotification'] = [
                'url'                   => $request->webhookUrl,
                'requireAcknowledgment' => 'true',
                'loggingEnabled'        => 'true',
                'includeHMAC'           => filled($this->setting->hmac_key) ? 'true' : 'false',
                'eventData'             => ['version' => 'restv2.1', 'format' => 'json'],
                'events'                => ['envelope-completed', 'envelope-declined', 'envelope-voided'],
            ];
        }

        $response = $this->api()->post('/envelopes', $body);
        $this->throwUnless($response, "L'envoi de l'enveloppe a été refusé");

        $envelopeId = (string) $response->json('envelopeId');
        if ($envelopeId === '') {
            throw new SignatureException("DocuSign n'a pas renvoyé d'identifiant d'enveloppe.");
        }

        return $envelopeId;
    }

    public function signingUrl(string $envelopeId, string $signerName, string $signerEmail, string $clientUserId, string $returnUrl): string
    {
        $response = $this->api()->post('/envelopes/' . rawurlencode($envelopeId) . '/views/recipient', [
            'returnUrl'            => $returnUrl,
            'authenticationMethod' => 'none',
            'email'                => $signerEmail,
            'userName'             => $signerName,
            'clientUserId'         => $clientUserId,
        ]);
        $this->throwUnless($response, "L'ouverture de la session de signature a été refusée");

        return (string) $response->json('url');
    }

    public function status(string $envelopeId): EnvelopeState
    {
        $response = $this->api()->get('/envelopes/' . rawurlencode($envelopeId), ['include' => 'recipients']);
        $this->throwUnless($response, "La lecture de l'enveloppe a échoué");

        $status = match ((string) $response->json('status')) {
            'completed' => QuoteSignature::STATUS_COMPLETED,
            'declined'  => QuoteSignature::STATUS_DECLINED,
            'voided'    => QuoteSignature::STATUS_VOIDED,
            'delivered' => QuoteSignature::STATUS_DELIVERED,
            default     => QuoteSignature::STATUS_SENT, // created, sent, correct…
        };

        $completedAt = $response->json('completedDateTime');
        $reason = match ($status) {
            QuoteSignature::STATUS_DECLINED => $response->json('recipients.signers.0.declinedReason'),
            QuoteSignature::STATUS_VOIDED   => $response->json('voidedReason'),
            default                         => null,
        };

        return new EnvelopeState(
            $status,
            $status === QuoteSignature::STATUS_COMPLETED && $completedAt ? CarbonImmutable::parse($completedAt) : null,
            filled($reason) ? (string) $reason : null,
        );
    }

    public function signedDocument(string $envelopeId): string
    {
        return $this->download($envelopeId, 'combined');
    }

    public function certificate(string $envelopeId): ?string
    {
        return $this->download($envelopeId, 'certificate');
    }

    public function cancel(string $envelopeId, string $reason): void
    {
        $response = $this->api()->put('/envelopes/' . rawurlencode($envelopeId), [
            'status'       => 'voided',
            'voidedReason' => mb_substr($reason, 0, 200),
        ]);
        $this->throwUnless($response, "L'annulation de l'enveloppe a été refusée");
    }

    /**
     * DocuSign Connect signe le corps brut en HMAC-SHA256, encodé base64, dans
     * X-DocuSign-Signature-1 (puis -2… pendant une rotation de clé). Sans clé HMAC
     * configurée la notification est acceptée : elle ne fait que déclencher une
     * relecture authentifiée de l'enveloppe, elle ne modifie rien par elle-même.
     */
    public function parseWebhook(Request $request): ?string
    {
        $secret = (string) $this->setting->hmac_key;

        if ($secret !== '') {
            $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));
            $valid = false;

            for ($i = 1; $i <= 5; $i++) {
                $given = (string) $request->header('X-DocuSign-Signature-' . $i, '');
                if ($given !== '' && hash_equals($expected, $given)) {
                    $valid = true;
                    break;
                }
            }

            if (! $valid) {
                throw new SignatureException('Signature HMAC DocuSign invalide.');
            }
        }

        $envelopeId = $request->json('data.envelopeId') ?? $request->json('envelopeId');

        return filled($envelopeId) ? (string) $envelopeId : null;
    }

    public function testConnection(): string
    {
        return (string) ($this->account()['account_name'] ?? $this->setting->account_id);
    }

    /* ───────────────────────────── Transport ───────────────────────────── */

    private function api(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(60)
            ->baseUrl($this->apiBaseUrl());
    }

    private function download(string $envelopeId, string $document): string
    {
        $response = Http::withToken($this->accessToken())
            ->accept('application/pdf')
            ->timeout(120)
            ->baseUrl($this->apiBaseUrl())
            ->get('/envelopes/' . rawurlencode($envelopeId) . '/documents/' . $document);
        $this->throwUnless($response, 'Le téléchargement du document signé a échoué');

        return $response->body();
    }

    private function apiBaseUrl(): string
    {
        return rtrim((string) $this->account()['base_uri'], '/') . '/restapi/v2.1/accounts/' . rawurlencode((string) $this->setting->account_id);
    }

    /**
     * Le compte configuré tel que DocuSign le décrit (nom, serveur d'API).
     *
     * @return array{account_id: string, account_name?: string, base_uri: string}
     */
    private function account(): array
    {
        return Cache::remember($this->cacheKey('account'), now()->addHours(12), function () {
            $response = Http::withToken($this->accessToken())
                ->acceptJson()
                ->timeout(30)
                ->get('https://' . $this->oauthHost() . '/oauth/userinfo');
            $this->throwUnless($response, 'La lecture du compte DocuSign a échoué');

            foreach ((array) $response->json('accounts', []) as $account) {
                if (strcasecmp((string) ($account['account_id'] ?? ''), (string) $this->setting->account_id) === 0) {
                    return $account;
                }
            }

            throw new SignatureException("L'utilisateur API n'a pas accès au compte {$this->setting->account_id} : vérifiez l'« API Account ID ».");
        });
    }

    private function accessToken(): string
    {
        $key = $this->cacheKey('token');

        if ($token = Cache::get($key)) {
            return $token;
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout(30)
            ->post('https://' . $this->oauthHost() . '/oauth/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $this->assertion(),
            ]);

        if ($response->json('error') === 'consent_required') {
            throw SignatureException::consentRequired($this->consentUrl(route('admin.integrations.esignature.index')));
        }
        $this->throwUnless($response, "L'authentification DocuSign a échoué");

        $token = (string) $response->json('access_token');
        $ttl = max(60, (int) $response->json('expires_in', 3600) - 300);
        Cache::put($key, $token, $ttl);

        return $token;
    }

    private function assertion(): string
    {
        $privateKey = openssl_pkey_get_private((string) $this->setting->private_key);
        if ($privateKey === false) {
            throw new SignatureException('La clé privée RSA est illisible : collez le bloc complet, de « -----BEGIN RSA PRIVATE KEY----- » à « -----END RSA PRIVATE KEY----- ».');
        }

        $now = time();
        $segments = [
            $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
            $this->base64Url(json_encode([
                'iss'   => $this->setting->integration_key,
                'sub'   => $this->setting->api_user_id,
                'aud'   => $this->oauthHost(),
                'iat'   => $now,
                'exp'   => $now + 3600,
                'scope' => self::SCOPES,
            ])),
        ];

        openssl_sign(implode('.', $segments), $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $segments[] = $this->base64Url($signature);

        return implode('.', $segments);
    }

    /**
     * Le jeton et le compte sont mis en cache par jeu d'identifiants : modifier la
     * configuration change la clé, l'ancien jeton n'est jamais réutilisé.
     */
    private function cacheKey(string $what): string
    {
        return 'esignature.docusign.' . $what . '.' . sha1(implode('|', [
            $this->setting->environment,
            $this->setting->integration_key,
            $this->setting->api_user_id,
            $this->setting->account_id,
            optional($this->setting->updated_at)->timestamp,
        ]));
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * @return array<string, string>
     */
    private function anchor(string $text): array
    {
        return [
            'anchorString'             => $text,
            'anchorUnits'              => 'pixels',
            'anchorXOffset'            => '0',
            'anchorYOffset'            => '0',
            'anchorIgnoreIfNotPresent' => 'false',
        ];
    }

    private function throwUnless(Response $response, string $context): void
    {
        if ($response->successful()) {
            return;
        }

        // Erreurs REST : {errorCode, message} ; erreurs OAuth : {error, error_description}.
        $detail = $response->json('message')
            ?? $response->json('error_description')
            ?? $response->json('errorCode')
            ?? $response->json('error')
            ?? ('HTTP ' . $response->status());

        throw new SignatureException($context . ' : ' . $detail);
    }
}
