<?php

namespace Tests\Feature;

use App\Models\Integrations\PdpInvoiceSubmission;
use App\Models\User;
use App\Models\Workflow\Invoices;
use App\Services\Integrations\Pdp\Contracts\PdpGateway;
use App\Services\Integrations\Pdp\Data\PdpInvoiceResult;
use App\Services\Integrations\Pdp\Data\PdpWebhookEvent;
use App\Services\Integrations\Pdp\Enums\PdpLifecycle;
use App\Services\Integrations\Pdp\PdpManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Dépôt et suivi depuis la fiche facture.
 *
 * Ces routes sont authentifiées par **session** : la carte est rendue dans une
 * page Blade et n'a pas de jeton porteur à présenter. La version précédente
 * visait la route API et envoyait un en-tête `Bearer` construit à partir d'une
 * balise `meta[name="api-token"]` qui n'existe nulle part dans les vues — d'où
 * un 401 systématique.
 */
class PdpInvoiceWebRoutesTest extends TestCase
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

        config()->set('services.pdp.default', 'fake');
        $this->app->make(PdpManager::class)->extend('fake', new FakePdpGateway());
    }

    public function test_a_logged_in_user_can_deposit_an_invoice(): void
    {
        $invoice = Invoices::factory()->create(['invoice_type' => 1, 'statu' => 2]);

        $response = $this->postJson(route('invoices.pdp.submit', $invoice->id));

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('pdp_invoice_submissions', [
            'invoice_id'       => $invoice->id,
            'provider'         => 'fake',
            'external_id'      => 'EXT-1',
            'lifecycle_status' => PdpLifecycle::Submitted->value,
        ]);
    }

    public function test_a_draft_invoice_cannot_be_deposited(): void
    {
        // Un dépôt est irréversible : le document part chez l'acheteur. Un
        // brouillon n'a pas à pouvoir sortir.
        $invoice = Invoices::factory()->create(['invoice_type' => 1, 'statu' => 1]);

        $this->postJson(route('invoices.pdp.submit', $invoice->id))->assertStatus(422);

        $this->assertDatabaseCount('pdp_invoice_submissions', 0);
    }

    public function test_a_platform_refusal_is_returned_as_a_readable_message(): void
    {
        $refusal = 'La facture n\'est pas conforme : [BR-CO-10] Somme des lignes incohérente';
        FakePdpGateway::$failWith = $refusal;

        $invoice = Invoices::factory()->create(['invoice_type' => 1, 'statu' => 2]);

        $this->postJson(route('invoices.pdp.submit', $invoice->id))
            ->assertStatus(422)
            ->assertJsonFragment(['message' => $refusal]);
    }

    public function test_polling_updates_the_stored_status(): void
    {
        $invoice = Invoices::factory()->create(['invoice_type' => 1, 'statu' => 2]);

        PdpInvoiceSubmission::create([
            'tenant_id'        => $invoice->user_id,
            'invoice_id'       => $invoice->id,
            'provider'         => 'fake',
            'external_id'      => 'EXT-1',
            'lifecycle_status' => PdpLifecycle::Submitted->value,
        ]);

        FakePdpGateway::$pollLifecycle = PdpLifecycle::Paid;

        $this->postJson(route('invoices.pdp.poll', $invoice->id))->assertOk();

        $this->assertDatabaseHas('pdp_invoice_submissions', [
            'invoice_id'       => $invoice->id,
            'lifecycle_status' => PdpLifecycle::Paid->value,
        ]);
    }

    public function test_an_anonymous_visitor_is_rejected(): void
    {
        auth()->logout();

        $invoice = Invoices::factory()->create(['invoice_type' => 1, 'statu' => 2]);

        $this->postJson(route('invoices.pdp.submit', $invoice->id))->assertStatus(401);
    }
}

/** Double de plateforme : ces routes ne doivent dépendre d'aucun fournisseur. */
class FakePdpGateway implements PdpGateway
{
    public static ?string $failWith = null;

    public static PdpLifecycle $pollLifecycle = PdpLifecycle::Submitted;

    public function key(): string
    {
        return 'fake';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function submit(Invoices $invoice): PdpInvoiceResult
    {
        if (self::$failWith !== null) {
            $message = self::$failWith;
            self::$failWith = null;
            throw new \RuntimeException($message);
        }

        return new PdpInvoiceResult('EXT-1', PdpLifecycle::Submitted);
    }

    public function poll(string $externalId, int $tenantId): PdpInvoiceResult
    {
        return new PdpInvoiceResult($externalId, self::$pollLifecycle);
    }

    public function parseWebhook(Request $request): ?PdpWebhookEvent
    {
        return null;
    }
}
