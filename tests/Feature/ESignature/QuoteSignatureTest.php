<?php

namespace Tests\Feature\ESignature;

use App\Models\Companies\CompaniesContacts;
use App\Models\Integrations\ESignatureSetting;
use App\Models\User;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\QuoteSignature;
use App\Services\Documents\DocumentPdfService;
use App\Services\Files\FileRole;
use App\Services\Integrations\Signature\Drivers\DocuSignGateway;
use App\Services\Invoicing\FacturXBuilder;
use App\Services\PdfThemeResolver;
use App\Services\Documents\SalesPrintLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

/**
 * Signature DocuSign d'un devis depuis son lien public.
 *
 * L'API DocuSign est simulée (jeton, compte, enveloppes, documents) ; la clé RSA
 * est réelle (fixture jetable) pour couvrir la signature de l'assertion JWT.
 * Le PDF du devis est remplacé par un double : son contenu est couvert ailleurs.
 */
class QuoteSignatureTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://demo.docusign.net/restapi/v2.1/accounts/acc-1';

    /** Statut renvoyé par le faux DocuSign pour l'enveloppe env-1. */
    private string $envelopeStatus = 'sent';

    private int $envelopesCreated = 0;

    private bool $consentRequired = false;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake(config('files.disk'));

        $this->app->bind(DocumentPdfService::class, fn ($app) => new class(
            $app->make(PdfThemeResolver::class),
            $app->make(FacturXBuilder::class),
            $app->make(SalesPrintLayout::class),
        ) extends DocumentPdfService {
            public function render($document): string
            {
                return '%PDF-1.4 quote';
            }
        });

        $this->fakeDocuSign();
    }

    /* ───────────────────────────── Page publique ───────────────────────────── */

    public function test_the_public_page_offers_signing_on_a_sent_quote(): void
    {
        $this->allowGuestVisitsWithoutIp();
        $this->configure();
        $quote = $this->sentQuote();

        $this->get(route('guest.quote.show', ['uuid' => $quote->uuid]))
            ->assertOk()
            ->assertSee(route('guest.quote.signature.start', ['uuid' => $quote->uuid]), false);
    }

    public function test_the_public_page_shows_nothing_when_signing_is_not_configured(): void
    {
        $this->allowGuestVisitsWithoutIp();
        $quote = $this->sentQuote();

        $this->get(route('guest.quote.show', ['uuid' => $quote->uuid]))
            ->assertOk()
            ->assertDontSee(route('guest.quote.signature.start', ['uuid' => $quote->uuid]), false);
    }

    public function test_only_a_sent_quote_can_be_signed(): void
    {
        $this->configure();

        foreach ([1, 3, 4, 5, 6] as $statu) {
            $quote = $this->sentQuote(['statu' => $statu]);

            $this->post(route('guest.quote.signature.start', ['uuid' => $quote->uuid]))
                ->assertRedirect(route('guest.quote.show', ['uuid' => $quote->uuid]))
                ->assertSessionHas('signature_error');
        }

        $this->assertSame(0, $this->envelopesCreated);
    }

    public function test_an_expired_quote_cannot_be_signed(): void
    {
        $this->configure();
        $quote = $this->sentQuote(['validity_date' => now()->subDay()->toDateString()]);

        $this->post(route('guest.quote.signature.start', ['uuid' => $quote->uuid]))
            ->assertSessionHas('signature_error');

        $this->assertSame(0, $this->envelopesCreated);
    }

    /* ───────────────────────────── Envoi ───────────────────────────── */

    public function test_signing_sends_the_quote_and_the_acceptance_page_then_redirects_to_docusign(): void
    {
        $this->configure();
        $quote = $this->sentQuote();

        $this->post(route('guest.quote.signature.start', ['uuid' => $quote->uuid]))
            ->assertRedirect('https://demo.docusign.net/signing/session-1');

        $signature = $quote->signatures()->sole();
        $this->assertSame('env-1', $signature->envelope_id);
        $this->assertSame(QuoteSignature::STATUS_SENT, $signature->status);
        $this->assertSame('client@example.test', $signature->signer_email);
        $this->assertNotNull($signature->client_user_id);

        Http::assertSent(function (ClientRequest $request) use ($signature) {
            if ($request->method() !== 'POST' || $request->url() !== self::API . '/envelopes') {
                return false;
            }

            $signer = $request['recipients']['signers'][0];

            return count($request['documents']) === 2
                && base64_decode($request['documents'][0]['documentBase64']) === '%PDF-1.4 quote'
                && str_starts_with(base64_decode($request['documents'][1]['documentBase64']), '%PDF')
                && $signer['email'] === 'client@example.test'
                && $signer['clientUserId'] === $signature->client_user_id
                && $signer['tabs']['signHereTabs'][0]['anchorString'] === DocuSignGateway::ANCHOR_SIGN
                && $request->hasHeader('Authorization', 'Bearer access-token');
        });

        // L'assertion JWT est signée RS256 au nom de l'utilisateur API.
        Http::assertSent(function (ClientRequest $request) {
            if ($request->url() !== 'https://account-d.docusign.com/oauth/token') {
                return false;
            }

            [, $claims] = explode('.', $request['assertion']);
            $claims = json_decode(base64_decode(strtr($claims, '-_', '+/')), true);

            return $request['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
                && $claims['iss'] === 'integration-key'
                && $claims['sub'] === 'api-user'
                && $claims['aud'] === 'account-d.docusign.com';
        });
    }

    public function test_coming_back_reuses_the_pending_envelope(): void
    {
        $this->configure();
        $quote = $this->sentQuote();

        $this->post(route('guest.quote.signature.start', ['uuid' => $quote->uuid]));
        $this->post(route('guest.quote.signature.start', ['uuid' => $quote->uuid]))
            ->assertRedirect('https://demo.docusign.net/signing/session-1');

        $this->assertSame(1, $this->envelopesCreated);
        $this->assertSame(1, $quote->signatures()->count());
    }

    public function test_a_quote_modified_since_sending_voids_the_old_envelope(): void
    {
        $this->configure();
        $quote = $this->sentQuote();

        $this->post(route('guest.quote.signature.start', ['uuid' => $quote->uuid]));
        QuoteLines::where('quotes_id', $quote->id)->update(['selling_price' => 999]);
        $this->post(route('guest.quote.signature.start', ['uuid' => $quote->uuid]));

        $this->assertSame(2, $this->envelopesCreated);
        $this->assertSame(
            [QuoteSignature::STATUS_VOIDED, QuoteSignature::STATUS_SENT],
            $quote->signatures()->orderBy('id')->pluck('status')->all(),
        );
        Http::assertSent(fn (ClientRequest $r) => $r->method() === 'PUT'
            && $r->url() === self::API . '/envelopes/env-1'
            && $r['status'] === 'voided');
    }

    public function test_email_mode_lets_docusign_send_the_request(): void
    {
        $this->configure(['signing_mode' => ESignatureSetting::MODE_EMAIL]);
        $quote = $this->sentQuote();

        $this->post(route('guest.quote.signature.start', ['uuid' => $quote->uuid]))
            ->assertRedirect(route('guest.quote.show', ['uuid' => $quote->uuid]))
            ->assertSessionHas('signature_info');

        $this->assertNull($quote->signatures()->sole()->client_user_id);
        Http::assertNotSent(fn (ClientRequest $r) => str_ends_with($r->url(), '/views/recipient'));
        Http::assertSent(fn (ClientRequest $r) => $r->url() === self::API . '/envelopes'
            && ! array_key_exists('clientUserId', $r['recipients']['signers'][0]));
    }

    public function test_consent_required_is_reported_with_the_consent_link(): void
    {
        $this->configure();
        $this->consentRequired = true;

        $this->actingAs(User::factory()->create());
        $this->withoutMiddleware([\App\Http\Middleware\CheckUserRole::class]);

        $this->postJson(route('admin.integrations.esignature.test'))
            ->assertOk()
            ->assertJson(['ok' => false])
            ->assertJsonPath('consent_url', fn ($url) => str_starts_with($url, 'https://account-d.docusign.com/oauth/auth?')
                && str_contains($url, 'client_id=integration-key'));
    }

    /* ───────────────────────────── Retour et synchronisation ───────────────────────────── */

    public function test_the_return_url_does_not_trust_the_browser(): void
    {
        $this->configure();
        $quote = $this->sentQuote();
        $this->post(route('guest.quote.signature.start', ['uuid' => $quote->uuid]));

        // DocuSign dit « pas encore signé » : event=signing_complete ne suffit pas.
        $this->get(route('guest.quote.signature.return', ['uuid' => $quote->uuid, 'event' => 'signing_complete']))
            ->assertRedirect(route('guest.quote.show', ['uuid' => $quote->uuid]));

        $this->assertSame(QuoteSignature::STATUS_SENT, $quote->signatures()->sole()->status);
        $this->assertSame(2, (int) $quote->fresh()->statu);
    }

    public function test_a_completed_envelope_files_the_signed_pdf_and_wins_the_quote(): void
    {
        $this->configure();
        $quote = $this->sentQuote();
        $this->post(route('guest.quote.signature.start', ['uuid' => $quote->uuid]));

        $this->envelopeStatus = 'completed';
        $this->get(route('guest.quote.signature.return', ['uuid' => $quote->uuid, 'event' => 'signing_complete']))
            ->assertSessionHas('signature_info', __('esignature.flash_signed'));

        $signature = $quote->signatures()->sole();
        $this->assertSame(QuoteSignature::STATUS_COMPLETED, $signature->status);
        $this->assertSame('2026-10-08 09:30:00', $signature->completed_at->format('Y-m-d H:i:s'));
        $this->assertSame(3, (int) $quote->fresh()->statu);

        $files = $quote->files()->get();
        $this->assertCount(2, $files);
        $this->assertTrue($files->every(fn ($f) => $f->pivot->role === FileRole::SIGNED));
        Storage::disk(config('files.disk'))->assertExists($signature->signedFile->path);
        $this->assertSame('%PDF-1.4 signed', Storage::disk(config('files.disk'))->get($signature->signedFile->path));

        // Une seconde notification ne range rien de plus.
        $this->postJson(route('api.integrations.esignature.webhook'), ['event' => 'envelope-completed', 'data' => ['envelopeId' => 'env-1']])
            ->assertOk();
        $this->assertCount(2, $quote->files()->get());
    }

    public function test_a_declined_envelope_keeps_the_quote_open_to_a_new_attempt(): void
    {
        $this->configure();
        $quote = $this->sentQuote();
        $this->post(route('guest.quote.signature.start', ['uuid' => $quote->uuid]));

        $this->envelopeStatus = 'declined';
        $this->get(route('guest.quote.signature.return', ['uuid' => $quote->uuid, 'event' => 'decline']))
            ->assertSessionHas('signature_error');

        $signature = $quote->signatures()->sole();
        $this->assertSame(QuoteSignature::STATUS_DECLINED, $signature->status);
        $this->assertSame('Prix trop élevé', $signature->status_reason);
        $this->assertSame(2, (int) $quote->fresh()->statu);
    }

    public function test_the_scheduled_sync_completes_pending_envelopes(): void
    {
        $this->configure();
        $quote = $this->sentQuote();
        $this->post(route('guest.quote.signature.start', ['uuid' => $quote->uuid]));

        $this->envelopeStatus = 'completed';
        $this->artisan('wem:esign:sync')->assertSuccessful();

        $this->assertTrue($quote->signatures()->sole()->isCompleted());
        $this->assertSame(3, (int) $quote->fresh()->statu);
    }

    /* ───────────────────────────── Notification ───────────────────────────── */

    public function test_the_webhook_rejects_a_bad_hmac_signature(): void
    {
        $this->configure(['hmac_key' => 'connect-secret']);
        $quote = $this->sentQuote();
        $this->post(route('guest.quote.signature.start', ['uuid' => $quote->uuid]));
        $this->envelopeStatus = 'completed';

        $this->postJson(route('api.integrations.esignature.webhook'), ['data' => ['envelopeId' => 'env-1']], ['X-DocuSign-Signature-1' => 'forged'])
            ->assertStatus(401);

        $this->assertSame(QuoteSignature::STATUS_SENT, $quote->signatures()->sole()->status);
    }

    public function test_the_webhook_accepts_a_valid_hmac_signature(): void
    {
        $this->configure(['hmac_key' => 'connect-secret']);
        $quote = $this->sentQuote();
        $this->post(route('guest.quote.signature.start', ['uuid' => $quote->uuid]));
        $this->envelopeStatus = 'completed';

        $body = json_encode(['event' => 'envelope-completed', 'data' => ['envelopeId' => 'env-1']]);
        $hmac = base64_encode(hash_hmac('sha256', $body, 'connect-secret', true));

        $this->call('POST', route('api.integrations.esignature.webhook'), [], [], [], [
            'CONTENT_TYPE'                => 'application/json',
            'HTTP_X_DOCUSIGN_SIGNATURE_1' => $hmac,
        ], $body)->assertOk();

        $this->assertTrue($quote->signatures()->sole()->isCompleted());
    }

    /* ───────────────────────────── Écran de configuration ───────────────────────────── */

    public function test_settings_are_stored_encrypted_and_secrets_kept_when_left_blank(): void
    {
        $this->withoutMiddleware([\App\Http\Middleware\CheckUserRole::class]);
        $this->actingAs(User::factory()->create());

        $payload = [
            'environment'     => 'demo',
            'integration_key' => 'integration-key',
            'api_user_id'     => 'api-user',
            'account_id'      => 'acc-1',
            'private_key'     => '-----BEGIN RSA PRIVATE KEY-----abc-----END RSA PRIVATE KEY-----',
            'hmac_key'        => 'connect-secret',
            'signing_mode'    => 'embedded',
            'is_active'       => '1',
        ];

        $this->put(route('admin.integrations.esignature.update'), $payload)
            ->assertRedirect(route('admin.integrations.esignature.index'));

        $raw = \DB::table('esignature_settings')->first();
        $this->assertStringNotContainsString('BEGIN RSA', $raw->private_key);
        $this->assertNotSame('connect-secret', $raw->hmac_key);

        // Secrets laissés vides → conservés.
        $this->put(route('admin.integrations.esignature.update'), array_merge($payload, ['private_key' => '', 'hmac_key' => '', 'environment' => 'production']))
            ->assertRedirect();

        $setting = ESignatureSetting::current();
        $this->assertSame(1, ESignatureSetting::count());
        $this->assertSame('production', $setting->environment);
        $this->assertStringContainsString('BEGIN RSA', $setting->private_key);
        $this->assertSame('connect-secret', $setting->hmac_key);

        $this->get(route('admin.integrations.esignature.index'))
            ->assertOk()
            ->assertDontSee('BEGIN RSA PRIVATE KEY-----abc', false)
            ->assertDontSee('connect-secret', false);
    }

    /* ───────────────────────────── Outils ───────────────────────────── */

    private function configure(array $overrides = []): ESignatureSetting
    {
        // Clé jetable propre aux tests (openssl_pkey_new exige un openssl.cnf absent sous Windows).
        $pem = file_get_contents(base_path('tests/Fixtures/esignature/test-rsa-key.pem'));

        return ESignatureSetting::create(array_merge([
            'provider'        => 'docusign',
            'environment'     => ESignatureSetting::ENV_DEMO,
            'integration_key' => 'integration-key',
            'api_user_id'     => 'api-user',
            'account_id'      => 'acc-1',
            'private_key'     => $pem,
            'signing_mode'    => ESignatureSetting::MODE_EMBEDDED,
            'is_active'       => true,
        ], $overrides));
    }

    /**
     * GuestController::logVisit() n'écrit pas `ip_address`, colonne NOT NULL :
     * MySQL (strict => false) y met '' sans broncher, SQLite refuse l'insertion.
     */
    private function allowGuestVisitsWithoutIp(): void
    {
        Schema::table('guest_visits', fn (Blueprint $table) => $table->string('ip_address')->nullable()->change());
    }

    private function sentQuote(array $attributes = []): Quotes
    {
        $quote = Quotes::factory()->create(array_merge([
            'statu'         => 2,
            'validity_date' => now()->addMonth()->toDateString(),
        ], $attributes));

        CompaniesContacts::whereKey($quote->companies_contacts_id)->update([
            'first_name' => 'Camille',
            'name'       => 'Martin',
            'mail'       => 'client@example.test',
        ]);

        QuoteLines::factory()->create(['quotes_id' => $quote->id, 'qty' => 2, 'selling_price' => 100, 'discount' => 0]);

        return $quote->fresh();
    }

    private function fakeDocuSign(): void
    {
        Http::fake(function (ClientRequest $request) {
            $url = $request->url();
            $method = $request->method();

            return match (true) {
                $url === 'https://account-d.docusign.com/oauth/token' && $this->consentRequired => Http::response(['error' => 'consent_required'], 400),
                $url === 'https://account-d.docusign.com/oauth/token' => Http::response(['access_token' => 'access-token', 'expires_in' => 3600]),
                $url === 'https://account-d.docusign.com/oauth/userinfo' => Http::response(['accounts' => [
                    ['account_id' => 'acc-1', 'account_name' => 'Tôlerie Test', 'base_uri' => 'https://demo.docusign.net', 'is_default' => true],
                ]]),
                $method === 'POST' && $url === self::API . '/envelopes' => Http::response(['envelopeId' => 'env-' . (++$this->envelopesCreated), 'status' => 'sent']),
                $method === 'POST' && str_ends_with($url, '/views/recipient') => Http::response(['url' => 'https://demo.docusign.net/signing/session-' . $this->envelopesCreated]),
                $method === 'PUT' && str_starts_with($url, self::API . '/envelopes/') => Http::response(['envelopeId' => 'env-1']),
                str_ends_with($url, '/documents/combined') => Http::response('%PDF-1.4 signed', 200, ['Content-Type' => 'application/pdf']),
                str_ends_with($url, '/documents/certificate') => Http::response('%PDF-1.4 certificate', 200, ['Content-Type' => 'application/pdf']),
                $method === 'GET' && str_starts_with($url, self::API . '/envelopes/') => Http::response([
                    'status'            => $this->envelopeStatus,
                    'completedDateTime' => $this->envelopeStatus === 'completed' ? '2026-10-08T09:30:00.0000000Z' : null,
                    'recipients'        => ['signers' => [['declinedReason' => $this->envelopeStatus === 'declined' ? 'Prix trop élevé' : null]]],
                ]),
                default => Http::response(['message' => 'unexpected ' . $method . ' ' . $url], 500),
            };
        });
    }
}
