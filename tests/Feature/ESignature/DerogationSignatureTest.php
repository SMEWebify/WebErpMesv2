<?php

namespace Tests\Feature\ESignature;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Models\Integrations\ESignatureSetting;
use App\Models\Quality\QualityDerogation;
use App\Models\Quality\QualityDerogationSignature;
use App\Models\User;
use App\Services\Files\FileRole;
use App\Services\Quality\DerogationApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Accord du client sur une dérogation, signé par DocuSign (API simulée).
 */
class DerogationSignatureTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://demo.docusign.net/restapi/v2.1/accounts/acc-1';

    private string $envelopeStatus = 'sent';

    private int $envelopesCreated = 0;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Cache::flush();
        Storage::fake(config('files.disk'));
        $this->fakeDocuSign();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_sending_mails_the_request_to_the_customer_contact(): void
    {
        $this->configure();
        $derogation = $this->derogation(['statu' => QualityDerogation::STATU_IN_PROGRESS]);

        $this->post(route('quality.derogation.signature.send', ['id' => $derogation->id]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $signature = $derogation->signatures()->sole();
        $this->assertSame('env-1', $signature->envelope_id);
        $this->assertSame('client@example.test', $signature->signer_email);
        $this->assertSame($this->user->id, (int) $signature->sent_by);
        // Une demande envoyée met la dérogation en attente du client.
        $this->assertSame(QualityDerogation::STATU_WAITING_CUSTOMER, (int) $derogation->fresh()->statu);

        // Toujours par e-mail DocuSign : aucune session intégrée.
        Http::assertSent(fn (ClientRequest $r) => $r->url() === self::API . '/envelopes'
            && count($r['documents']) === 1
            && str_starts_with(base64_decode($r['documents'][0]['documentBase64']), '%PDF')
            && ! array_key_exists('clientUserId', $r['recipients']['signers'][0]));
    }

    public function test_the_signature_is_offered_even_when_public_quote_signing_is_off(): void
    {
        $this->configure(['is_active' => false]);
        $derogation = $this->derogation();

        $this->post(route('quality.derogation.signature.send', ['id' => $derogation->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $this->envelopesCreated);
    }

    public function test_an_internal_derogation_or_a_contact_without_email_is_refused(): void
    {
        $this->configure();

        $internal = $this->derogation(['type' => QualityDerogation::TYPE_INTERNAL]);
        $this->post(route('quality.derogation.signature.send', ['id' => $internal->id]))
            ->assertSessionHasErrors('signature');

        $noMail = $this->derogation();
        CompaniesContacts::whereKey($noMail->companies_contacts_id)->update(['mail' => '']);
        $this->post(route('quality.derogation.signature.send', ['id' => $noMail->id]))
            ->assertSessionHasErrors('signature');

        $this->assertSame(0, $this->envelopesCreated);
    }

    public function test_sending_twice_keeps_the_pending_envelope(): void
    {
        $this->configure();
        $derogation = $this->derogation();

        $this->post(route('quality.derogation.signature.send', ['id' => $derogation->id]));
        $this->post(route('quality.derogation.signature.send', ['id' => $derogation->id]));

        $this->assertSame(1, $this->envelopesCreated);
    }

    public function test_changing_the_derogation_voids_the_pending_envelope(): void
    {
        $this->configure();
        $derogation = $this->derogation();
        $this->post(route('quality.derogation.signature.send', ['id' => $derogation->id]));

        $this->post(route('quality.derogation.update', ['id' => $derogation->id]), [
            'label'    => $derogation->label,
            'statu'    => $derogation->statu,
            'user_id'  => $derogation->user_id,
            'pb_descp' => $derogation->pb_descp,
            'proposal' => 'Retouche puis livraison',
        ])->assertSessionHas('warning', __('derogations.voided_after_change'));

        $this->assertSame(QualityDerogationSignature::STATUS_VOIDED, $derogation->signatures()->sole()->status);
        Http::assertSent(fn (ClientRequest $r) => $r->method() === 'PUT'
            && $r->url() === self::API . '/envelopes/env-1'
            && $r['status'] === 'voided');
    }

    public function test_a_signed_envelope_files_the_proof_and_fills_the_reply_without_validating(): void
    {
        $this->configure();
        $derogation = $this->derogation(['decision' => 'Livraison en l\'état']);
        $this->post(route('quality.derogation.signature.send', ['id' => $derogation->id]));

        $this->envelopeStatus = 'completed';
        $this->post(route('quality.derogation.signature.sync', ['id' => $derogation->id]))
            ->assertSessionHasNoErrors();

        $derogation->refresh();
        $signature = $derogation->signatures()->sole();
        $this->assertTrue($signature->isCompleted());
        $this->assertSame('2026-10-08', $derogation->customer_reply_date->toDateString());
        $this->assertSame('Camille Martin', $derogation->customer_signatory);
        $this->assertStringContainsString('Camille Martin', $derogation->reply);

        $files = $derogation->files()->get();
        $this->assertCount(2, $files);
        $this->assertTrue($files->every(fn ($f) => $f->pivot->role === FileRole::SIGNED));

        // La validation reste un acte de l'entreprise, mais plus rien ne manque.
        $this->assertFalse($derogation->isValidated());
        $this->assertSame([], app(DerogationApproval::class)->missingForValidation($derogation));
    }

    public function test_the_webhook_and_the_scheduled_sync_handle_derogation_envelopes(): void
    {
        $this->configure();
        $first = $this->derogation();
        $this->post(route('quality.derogation.signature.send', ['id' => $first->id]));
        $second = $this->derogation();
        $this->post(route('quality.derogation.signature.send', ['id' => $second->id]));

        $this->envelopeStatus = 'completed';

        $this->postJson(route('api.integrations.esignature.webhook'), ['data' => ['envelopeId' => 'env-1']])
            ->assertOk();
        $this->assertTrue($first->signatures()->sole()->isCompleted());

        $this->artisan('wem:esign:sync')->assertSuccessful();
        $this->assertTrue($second->signatures()->sole()->isCompleted());
    }

    public function test_the_request_document_can_be_previewed(): void
    {
        $derogation = $this->derogation();

        $response = $this->get(route('quality.derogation.preview', ['id' => $derogation->id]))->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /* ───────────────────────────── Outils ───────────────────────────── */

    private function configure(array $overrides = []): ESignatureSetting
    {
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

    private function derogation(array $attributes = []): QualityDerogation
    {
        $company = Companies::factory()->create(['statu_customer' => 2]);
        $contact = CompaniesContacts::factory()->create([
            'companies_id' => $company->id,
            'first_name'   => 'Camille',
            'name'         => 'Martin',
            'mail'         => 'client@example.test',
        ]);

        return QualityDerogation::factory()->create(array_merge([
            'type'                  => QualityDerogation::TYPE_EXTERNAL,
            'statu'                 => QualityDerogation::STATU_WAITING_CUSTOMER,
            'user_id'               => $this->user->id,
            'companies_id'          => $company->id,
            'companies_contacts_id' => $contact->id,
        ], $attributes));
    }

    private function fakeDocuSign(): void
    {
        Http::fake(function (ClientRequest $request) {
            $url = $request->url();
            $method = $request->method();

            return match (true) {
                $url === 'https://account-d.docusign.com/oauth/token' => Http::response(['access_token' => 'access-token', 'expires_in' => 3600]),
                $url === 'https://account-d.docusign.com/oauth/userinfo' => Http::response(['accounts' => [
                    ['account_id' => 'acc-1', 'account_name' => 'Tôlerie Test', 'base_uri' => 'https://demo.docusign.net', 'is_default' => true],
                ]]),
                $method === 'POST' && $url === self::API . '/envelopes' => Http::response(['envelopeId' => 'env-' . (++$this->envelopesCreated), 'status' => 'sent']),
                $method === 'PUT' && str_starts_with($url, self::API . '/envelopes/') => Http::response(['envelopeId' => 'env-1']),
                str_ends_with($url, '/documents/combined') => Http::response('%PDF-1.4 signed', 200, ['Content-Type' => 'application/pdf']),
                str_ends_with($url, '/documents/certificate') => Http::response('%PDF-1.4 certificate', 200, ['Content-Type' => 'application/pdf']),
                $method === 'GET' && str_starts_with($url, self::API . '/envelopes/') => Http::response([
                    'status'            => $this->envelopeStatus,
                    'completedDateTime' => $this->envelopeStatus === 'completed' ? '2026-10-08T09:30:00.0000000Z' : null,
                    'recipients'        => ['signers' => [['declinedReason' => null]]],
                ]),
                default => Http::response(['message' => 'unexpected ' . $method . ' ' . $url], 500),
            };
        });
    }
}
