<?php

namespace Tests\Feature;

use App\Models\Companies\Companies;
use App\Models\Integrations\PdpIncomingInvoice;
use App\Models\User;
use App\Services\Integrations\Pdp\Enums\PdpOutgoingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Statuts déclarés au fournisseur sur les factures reçues.
 *
 * La réforme n'oblige pas seulement à recevoir : l'acheteur doit renvoyer le
 * cycle de vie du document. Le fournisseur doit savoir si sa facture est prise
 * en charge, approuvée ou refusée, et l'administration en déduit l'exigibilité
 * de la TVA sur les prestations de services.
 */
class PdpOutgoingStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        $this->actingAs(User::factory()->create());

        config()->set('services.pdp.default', 'superpdp');
        config()->set('services.superpdp.client_id', 'client-id');
        config()->set('services.superpdp.client_secret', 'client-secret');
        config()->set('services.superpdp.base_url', 'https://api.superpdp.tech');

        Cache::flush();
    }

    public function test_a_status_is_declared_to_the_platform(): void
    {
        $this->fakeHttp();
        $incoming = $this->makeIncoming();

        $this->postJson(route('purchases.incoming.status', $incoming->id), [
            'status' => PdpOutgoingStatus::Acknowledged->value,
        ])->assertOk();

        Http::assertSent(fn (ClientRequest $r) => $this->isEventCreation($r)
            && $r->data()['invoice_id'] === 326198
            && $r->data()['status_code'] === 'fr:204');
    }

    public function test_a_refusal_carries_its_reason_to_the_supplier(): void
    {
        $this->fakeHttp();
        $incoming = $this->makeIncoming();

        $this->postJson(route('purchases.incoming.status', $incoming->id), [
            'status' => PdpOutgoingStatus::Refused->value,
            'note'   => 'Quantités facturées supérieures au bon de réception.',
        ])->assertOk();

        Http::assertSent(function (ClientRequest $r) {
            if (! $this->isEventCreation($r)) {
                return false;
            }
            $note = $r->data()['details'][0]['notes'][0]['contents'][0]['content'] ?? null;

            return $r->data()['status_code'] === 'fr:210'
                && $note === 'Quantités facturées supérieures au bon de réception.';
        });

        // Un refus déclaré doit sortir le document de la file à traiter.
        $this->assertSame(PdpIncomingInvoice::STATUS_REJECTED, $incoming->fresh()->status);
    }

    public function test_a_refusal_without_reason_is_rejected_before_leaving(): void
    {
        // Refuser sans motif laisse le fournisseur sans moyen de corriger : la
        // norme l'interdit, autant le bloquer ici plutôt que chez la plateforme.
        $this->fakeHttp();
        $incoming = $this->makeIncoming();

        $this->postJson(route('purchases.incoming.status', $incoming->id), [
            'status' => PdpOutgoingStatus::Refused->value,
        ])->assertStatus(422);

        Http::assertNotSent(fn (ClientRequest $r) => $this->isEventCreation($r));
        $this->assertSame(PdpIncomingInvoice::STATUS_RECEIVED, $incoming->fresh()->status);
    }

    public function test_a_manually_uploaded_document_has_no_one_to_report_to(): void
    {
        $this->fakeHttp();
        $incoming = $this->makeIncoming(['provider' => 'manual', 'external_id' => null]);

        $this->postJson(route('purchases.incoming.status', $incoming->id), [
            'status' => PdpOutgoingStatus::Acknowledged->value,
        ])->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'déposé à la main'));

        Http::assertNotSent(fn (ClientRequest $r) => $this->isEventCreation($r));
    }

    public function test_a_platform_refusal_surfaces_as_a_readable_message(): void
    {
        Http::fake([
            'https://api.superpdp.tech/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
            'https://api.superpdp.tech/v1.beta/invoice_events' => Http::response(['message' => 'invalid status transition'], 422),
        ]);

        $incoming = $this->makeIncoming();

        $this->postJson(route('purchases.incoming.status', $incoming->id), [
            'status' => PdpOutgoingStatus::Approved->value,
        ])->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'invalid status transition'));
    }

    /* ------------------------------------------------------------- Utilitaires */

    private function isEventCreation(ClientRequest $request): bool
    {
        return $request->method() === 'POST'
            && str_ends_with($request->url(), '/v1.beta/invoice_events');
    }

    private function fakeHttp(): void
    {
        Http::fake([
            'https://api.superpdp.tech/oauth2/token'          => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
            'https://api.superpdp.tech/v1.beta/invoice_events' => Http::response(['id' => 1]),
        ]);
    }

    private function makeIncoming(array $attributes = []): PdpIncomingInvoice
    {
        return PdpIncomingInvoice::create($attributes + [
            'provider'            => 'superpdp',
            'external_id'         => '326198',
            'supplier_company_id' => Companies::factory()->create(['label' => 'Tricatel'])->id,
            'seller_name'         => 'Tricatel',
            'invoice_number'      => 'F-2026-77',
            'status'              => PdpIncomingInvoice::STATUS_RECEIVED,
        ]);
    }
}
