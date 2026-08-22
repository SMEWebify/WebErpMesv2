<?php

namespace Tests\Feature;

use App\Models\Companies\Companies;
use App\Models\Integrations\PdpIncomingInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rapprochement d'une facture électronique reçue avec les réceptions en attente
 * de facturation.
 *
 * Les lignes du document du fournisseur ne deviennent jamais des lignes de
 * facture d'achat — celles-ci référencent une commande et une réception. Le
 * document est transmis à l'écran de saisie pour être **confronté** à la
 * sélection, ce que ce test vérifie.
 */
class PurchaseReconciliationTest extends TestCase
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
    }

    public function test_the_received_document_is_handed_to_the_reconciliation_screen(): void
    {
        $incoming = $this->makeIncoming();

        $response = $this->getJson(route('purchases.waiting.invoice.json.init', ['incoming_id' => $incoming->id]));

        $response->assertOk()
            ->assertJsonPath('incoming.invoice_number', 'F-2026-77')
            ->assertJsonPath('incoming.seller_name', 'Tricatel')
            ->assertJsonPath('incoming.total_ht', 1560.46)
            ->assertJsonPath('incoming.lines.0.name', 'Poulet aux hormones')
            ->assertJsonPath('incoming.lines.1.line_total', fn ($total) => (float) $total === 1500.0);
    }

    public function test_without_context_the_screen_stays_a_plain_invoice_form(): void
    {
        $this->makeIncoming();

        $this->getJson(route('purchases.waiting.invoice.json.init'))
            ->assertOk()
            ->assertJsonPath('incoming', null);
    }

    public function test_an_unknown_document_is_ignored_rather_than_fatal(): void
    {
        // L'identifiant vient de l'URL : il peut être périmé (document déjà
        // traité, lien collé) sans que l'écran doive tomber en erreur.
        $this->getJson(route('purchases.waiting.invoice.json.init', ['incoming_id' => 99999]))
            ->assertOk()
            ->assertJsonPath('incoming', null);
    }

    private function makeIncoming(): PdpIncomingInvoice
    {
        $supplier = Companies::factory()->create(['label' => 'Tricatel', 'statu_supplier' => 2]);

        return PdpIncomingInvoice::create([
            'provider'            => 'superpdp',
            'external_id'         => '326198',
            'supplier_company_id' => $supplier->id,
            'seller_name'         => 'Tricatel',
            'seller_vat'          => 'FR15000000001',
            'invoice_number'      => 'F-2026-77',
            'issue_date'          => '2026-06-30',
            'due_date'            => '2026-07-30',
            'currency'            => 'EUR',
            'total_ht'            => 1560.46,
            'total_vat'           => 303.33,
            'total_ttc'           => 1863.79,
            'status'              => PdpIncomingInvoice::STATUS_RECEIVED,
            'payload'             => [
                'lines' => [
                    ['name' => 'Poulet aux hormones',  'quantity' => 28.52, 'unit_code' => 'KGM', 'line_total' => 60.46],
                    ['name' => 'Conseil en stratégie', 'quantity' => 1,     'unit_code' => 'C62', 'line_total' => 1500],
                ],
            ],
        ]);
    }
}
