<?php

namespace Tests\Feature;

use App\Models\Integrations\PdpSyncCursor;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Services\Integrations\Pdp\Drivers\SuperPdpGateway;
use App\Services\Integrations\Pdp\Enums\PdpLifecycle;
use App\Services\Invoicing\FacturXBuilder;
use App\Services\PdfThemeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Driver SUPER PDP : dépôt du Factur-X, traduction des statuts et
 * synchronisation par curseur.
 *
 * La génération du Factur-X est remplacée par un double : ces tests portent sur
 * le dialogue avec la plateforme, pas sur le contenu du PDF.
 */
class SuperPdpGatewayTest extends TestCase
{
    use RefreshDatabase;

    /** Public : lu depuis la classe anonyme qui double FacturXBuilder. */
    public const FAKE_PDF = '%PDF-1.7 fake-facturx';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.superpdp.client_id', 'client-id');
        config()->set('services.superpdp.client_secret', 'client-secret');
        config()->set('services.superpdp.base_url', 'https://api.superpdp.tech');
        config()->set('services.superpdp.processing_rule', 'B2B');
        config()->set('services.superpdp.pre_validate', true);

        Cache::flush();

        $this->app->bind(FacturXBuilder::class, fn ($app) => new class($app->make(PdfThemeResolver::class)) extends FacturXBuilder {
            public function buildPdf(Invoices $invoice): string
            {
                return SuperPdpGatewayTest::FAKE_PDF;
            }
        });
    }

    public function test_submit_deposits_the_facturx_document_and_records_the_platform_id(): void
    {
        $this->fakeHttp([
            'https://api.superpdp.tech/v1.beta/invoices?*' => Http::response(['id' => 4242, 'events' => []]),
        ]);

        $invoice = $this->makeInvoiceWithSingleLine();

        $result = $this->gateway()->submit($invoice);

        $this->assertSame('4242', $result->externalId);
        $this->assertSame(PdpLifecycle::Submitted, $result->lifecycle);

        Http::assertSent(function (ClientRequest $request) {
            if ($request->method() !== 'POST' || ! str_contains($request->url(), '/v1.beta/invoices?')) {
                return false;
            }

            // Le document envoyé est bien le nôtre, en PDF, tel quel.
            return $request->body() === self::FAKE_PDF
                && $request->hasHeader('Content-Type', 'application/pdf')
                && $request->hasHeader('Authorization', 'Bearer access-token')
                && str_contains(urldecode($request->url()), 'external_id=FA-2026-001')
                && str_contains(urldecode($request->url()), 'processing_rule=B2B');
        });
    }

    public function test_the_most_recent_event_decides_the_status(): void
    {
        // Les statuts s'accumulent sans se remplacer : un refus postérieur à une
        // acceptation doit l'emporter, sinon WEM afficherait une facture
        // « acceptée » alors que le client vient de la contester.
        $this->fakeHttp([
            'https://api.superpdp.tech/v1.beta/invoices/4242*' => Http::response([
                'id'     => 4242,
                'events' => [
                    ['id' => 10, 'invoice_id' => 4242, 'status_code' => 'fr:205', 'status_text' => 'Approuvée'],
                    ['id' => 11, 'invoice_id' => 4242, 'status_code' => 'fr:210', 'status_text' => 'Refusée par le client'],
                ],
            ]),
        ]);

        $result = $this->gateway()->poll('4242', 0);

        $this->assertSame(PdpLifecycle::Refused, $result->lifecycle);
        $this->assertStringContainsString('Refusée par le client', (string) $result->rejectionReason);
    }

    public function test_a_document_rejected_by_the_validator_is_never_deposited(): void
    {
        $this->fakeHttp([
            'https://api.superpdp.tech/v1.beta/validation_reports' => Http::response([
                'data' => [[
                    'is_valid'   => false,
                    'subreports' => [[
                        'validator' => 'EN16931-CII.xsl',
                        'failures'  => [['message' => '[BR-CO-10] Somme des lignes incohérente', 'raw' => '']],
                    ]],
                ]],
            ]),
        ]);

        $invoice = $this->makeInvoiceWithSingleLine();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/BR-CO-10/');

        try {
            $this->gateway()->submit($invoice);
        } finally {
            Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), '/v1.beta/invoices?'));
        }
    }

    public function test_a_warning_alone_blocks_the_deposit(): void
    {
        // Une assertion « flag=warning » suffit à faire tomber `is_valid`, et la
        // plateforme rejette ensuite le document en fr:213 / REJ_SEMAN en citant
        // cette règle. Constaté en conditions réelles : PEPPOL-EN16931-R008,
        // libellée « still status warning », a fait rejeter quatre factures.
        // Le mot « warning » qualifie la sévérité dans le schematron, pas le
        // sort de la facture — laisser passer reviendrait à promettre un envoi
        // qui échouera silencieusement des heures plus tard.
        $this->fakeHttp([
            'https://api.superpdp.tech/v1.beta/validation_reports' => Http::response([
                'data' => [[
                    'is_valid'   => false,
                    'subreports' => [[
                        'validator' => 'FACTUR-X_EN16931.xslt',
                        'messages'  => [['message' => 'Document MUST not contain empty elements. (still status warning)', 'raw' => '', 'rule' => 'PEPPOL-EN16931-R008']],
                        'failures'  => [],
                    ]],
                ]],
            ]),
            'https://api.superpdp.tech/v1.beta/invoices?*' => Http::response(['id' => 91, 'events' => []]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/PEPPOL-EN16931-R008/');

        try {
            $this->gateway()->submit($this->makeInvoiceWithSingleLine());
        } finally {
            Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), '/v1.beta/invoices?'));
        }
    }

    public function test_the_refusal_message_names_the_rule_that_failed(): void
    {
        // Un rapport sans motif exploitable laisserait l'utilisateur devant
        // « motif non précisé » : le code de règle doit toujours ressortir.
        $this->fakeHttp([
            'https://api.superpdp.tech/v1.beta/validation_reports' => Http::response([
                'data' => [[
                    'is_valid'   => false,
                    'subreports' => [[
                        'validator' => 'EN16931-CII.xsl',
                        'messages'  => [],
                        'failures'  => [['message' => 'Somme des lignes incohérente', 'raw' => '', 'rule' => 'BR-CO-10']],
                    ]],
                ]],
            ]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/\[BR-CO-10\] Somme des lignes incohérente/');

        $this->gateway()->submit($this->makeInvoiceWithSingleLine());
    }

    public function test_an_expired_token_is_refreshed_once_and_the_call_replayed(): void
    {
        Http::fake([
            'https://api.superpdp.tech/oauth2/token' => Http::sequence()
                ->push(['access_token' => 'stale-token', 'expires_in' => 1800])
                ->push(['access_token' => 'fresh-token', 'expires_in' => 1800]),
            'https://api.superpdp.tech/v1.beta/validation_reports' => Http::response(['data' => [['is_valid' => true]]]),
            'https://api.superpdp.tech/v1.beta/invoices?*' => Http::sequence()
                ->push(['message' => 'expired'], 401)
                ->push(['id' => 77, 'events' => []]),
        ]);

        $result = $this->gateway()->submit($this->makeInvoiceWithSingleLine());

        $this->assertSame('77', $result->externalId);
        Http::assertSent(fn (ClientRequest $r) => str_contains($r->url(), '/v1.beta/invoices?')
            && $r->hasHeader('Authorization', 'Bearer fresh-token'));
    }

    public function test_fetch_events_reads_from_the_cursor_and_ignores_fiscal_statuses(): void
    {
        PdpSyncCursor::advance('superpdp', PdpSyncCursor::STREAM_EVENTS, 5);

        $this->fakeHttp([
            'https://api.superpdp.tech/v1.beta/invoice_events*' => Http::response([
                'data' => [
                    // Échange technique avec le portail public : ne dit rien du
                    // sort de la facture, doit être ignoré.
                    ['id' => 6, 'invoice_id' => 88, 'status_code' => 'ppf:validated-ack'],
                    ['id' => 7, 'invoice_id' => 88, 'status_code' => 'fr:212', 'status_text' => 'Paiement reçu'],
                ],
                'has_after' => false,
            ]),
        ]);

        $events = $this->gateway()->fetchEvents(0);

        $this->assertCount(1, $events);
        $this->assertSame('88', $events[0]->externalId);
        $this->assertSame(PdpLifecycle::Paid, $events[0]->lifecycle);

        Http::assertSent(fn (ClientRequest $r) => str_contains($r->url(), 'invoice_events')
            && str_contains(urldecode($r->url()), 'starting_after_id=5'));

        // Le curseur n'avance que sur validation explicite.
        $this->assertSame(5, PdpSyncCursor::positionOf('superpdp', PdpSyncCursor::STREAM_EVENTS));
        $this->gateway()->commitEvents(0, 7);
        $this->assertSame(7, PdpSyncCursor::positionOf('superpdp', PdpSyncCursor::STREAM_EVENTS));
    }

    public function test_a_batch_of_only_ignored_events_still_moves_the_cursor(): void
    {
        // Sinon ces mêmes événements seraient relus à chaque exécution, sans fin.
        $this->fakeHttp([
            'https://api.superpdp.tech/v1.beta/invoice_events*' => Http::response([
                'data'      => [['id' => 12, 'invoice_id' => 88, 'status_code' => 'ppf:flow-1-ack']],
                'has_after' => false,
            ]),
        ]);

        $this->assertSame([], $this->gateway()->fetchEvents(0));
        $this->assertSame(12, PdpSyncCursor::positionOf('superpdp', PdpSyncCursor::STREAM_EVENTS));
    }

    public function test_fetch_inbound_downloads_documents_and_leaves_the_cursor_untouched(): void
    {
        $this->fakeHttp([
            'https://api.superpdp.tech/v1.beta/invoices/9/download' => Http::response('<rsm:CrossIndustryInvoice/>'),
            'https://api.superpdp.tech/v1.beta/invoices?*'          => Http::response([
                'data'      => [['id' => 9, 'direction' => 'in']],
                'has_after' => false,
            ]),
        ]);

        $entries = $this->gateway()->fetchInbound(0);

        $this->assertCount(1, $entries);
        $this->assertSame('9', $entries[0]['external_id']);
        $this->assertSame('<rsm:CrossIndustryInvoice/>', $entries[0]['content']);

        Http::assertSent(fn (ClientRequest $r) => str_contains($r->url(), '/v1.beta/invoices?')
            && str_contains(urldecode($r->url()), 'direction=in'));

        // Un document illisible ne doit pas être perdu : tant que la
        // synchronisation n'a pas validé, il sera relu.
        $this->assertSame(0, PdpSyncCursor::positionOf('superpdp', PdpSyncCursor::STREAM_INVOICES_IN));
        $this->gateway()->commitInbound(0, 9);
        $this->assertSame(9, PdpSyncCursor::positionOf('superpdp', PdpSyncCursor::STREAM_INVOICES_IN));
    }

    public function test_the_driver_is_disabled_without_credentials(): void
    {
        config()->set('services.superpdp.client_id', null);

        $this->assertFalse($this->gateway()->isEnabled());
    }

    /* ------------------------------------------------------------- Utilitaires */

    private function gateway(): SuperPdpGateway
    {
        return $this->app->make(SuperPdpGateway::class);
    }

    /** Ajoute aux stubs donnés le jeton OAuth et une pré-validation conforme. */
    private function fakeHttp(array $stubs): void
    {
        Http::fake($stubs + [
            'https://api.superpdp.tech/oauth2/token'              => Http::response(['access_token' => 'access-token', 'expires_in' => 1800]),
            'https://api.superpdp.tech/v1.beta/validation_reports' => Http::response(['data' => [['is_valid' => true]]]),
        ]);
    }

    private function makeInvoiceWithSingleLine(): Invoices
    {
        $invoice = Invoices::factory()->create([
            'code'         => 'FA-2026-001',
            'invoice_type' => 1,
            'statu'        => 2, // seule une facture émise peut être déposée
            'due_date'     => '2026-09-30',
        ]);

        $orderLine = OrderLines::factory()->create([
            'code'  => 'PART-1',
            'label' => 'Tôle pliée 3mm',
        ]);

        InvoiceLines::create([
            'invoices_id'    => $invoice->id,
            'order_line_id'  => $orderLine->id,
            'ordre'          => 10,
            'qty'            => 4,
            'unit_price'     => 125.50,
            'discount'       => 10,
            'vat_rate'       => 20,
            'invoice_status' => 1,
        ]);

        return $invoice->fresh();
    }
}
