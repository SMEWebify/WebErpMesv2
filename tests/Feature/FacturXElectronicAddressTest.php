<?php

namespace Tests\Feature;

use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Services\Invoicing\FacturXBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Adresses électroniques de facturation dans le Factur-X (BT-34 / BT-49).
 *
 * C'est sur elles que la plateforme achemine le document. Une erreur ici n'est
 * pas visible sur le PDF : la facture part et n'arrive nulle part.
 */
class FacturXElectronicAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_explicit_address_is_used_for_both_parties(): void
    {
        // Cas du bac à sable, et de toute entreprise ayant ouvert une ligne
        // d'annuaire dédiée : l'adresse n'a aucun rapport avec le SIREN.
        $this->makeFactory(['siren' => '000000002', 'electronic_address' => '315143296_59359']);

        $invoice = $this->makeInvoice(['siren' => '000000001', 'electronic_address' => '315143296_59358']);

        $xml = $this->buildXml($invoice);

        $this->assertStringContainsString('<ram:URIID schemeID="0225">315143296_59359</ram:URIID>', $xml);
        $this->assertStringContainsString('<ram:URIID schemeID="0225">315143296_59358</ram:URIID>', $xml);
    }

    public function test_the_siren_is_the_default_address_in_the_french_scheme(): void
    {
        // Choix pragmatique de la majorité des entreprises françaises : une
        // seule adresse de facturation, égale au SIREN.
        $this->makeFactory(['siren' => '853322915', 'electronic_address' => null]);

        $invoice = $this->makeInvoice(['siren' => '552081317', 'electronic_address' => null]);

        $xml = $this->buildXml($invoice);

        $this->assertStringContainsString('<ram:URIID schemeID="0225">853322915</ram:URIID>', $xml);
        $this->assertStringContainsString('<ram:URIID schemeID="0225">552081317</ram:URIID>', $xml);
    }

    public function test_no_address_is_invented_outside_the_french_scheme(): void
    {
        // Hors annuaire français, le SIREN n'identifie rien : émettre une
        // adresse inventée ferait échouer l'acheminement de façon opaque.
        //
        // Le repli étant refusé, il ne reste aucune adresse : assertPartiesAreIdentifiable
        // arrête donc la facture ici. C'est le comportement voulu — mieux vaut
        // refuser en clair que déposer un document qui n'arrivera nulle part.
        $this->makeFactory(['siren' => '853322915', 'electronic_address' => null]);

        $invoice = $this->makeInvoice([
            'siren'                     => '0869763267',
            'electronic_address'        => null,
            'electronic_address_scheme' => '0208', // Belgique
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('BT-49');

        $this->buildXml($invoice);
    }

    /* ------------------------------------------------------------- Utilitaires */

    private function buildXml(Invoices $invoice): string
    {
        return $this->app->make(FacturXBuilder::class)->buildXml($invoice);
    }

    /**
     * TestCase crée déjà une société « Test Factory », et le singleton résout
     * Factory::first() : il faut donc renseigner CETTE ligne, pas en ajouter
     * une seconde qui ne serait jamais lue.
     */
    private function makeFactory(array $attributes): void
    {
        $factory = Factory::first() ?? new Factory();

        $factory->fill($attributes + [
            'name'     => 'Burger Queen',
            'address'  => '809 avenue du Languedoc',
            'zipcode'  => '12100',
            'city'     => 'Millau',
            'country'  => 'FR',
            'vat_num'  => 'FR18000000002',
            'curency'  => 'EUR',
        ])->save();

        // Le singleton peut avoir été résolu avant : forcer sa relecture.
        $this->app->forgetInstance('Factory');
    }

    private function makeInvoice(array $clientAttributes): Invoices
    {
        $client = Companies::factory()->create($clientAttributes + [
            'label'               => 'Tricatel',
            'statu_customer'      => 2,
            'intra_community_vat' => 'FR15000000001',
        ]);

        $invoice = Invoices::factory()->create([
            'code'         => 'FA-2026-042',
            'companies_id' => $client->id,
            'invoice_type' => 1,
            'statu'        => 2, // émise : le builder refuse les brouillons
            'due_date'     => '2026-09-30',
        ]);

        $orderLine = OrderLines::factory()->create(['code' => 'PART-1', 'label' => 'Tôle pliée 3mm']);

        InvoiceLines::create([
            'invoices_id'    => $invoice->id,
            'order_line_id'  => $orderLine->id,
            'ordre'          => 10,
            'qty'            => 2,
            'unit_price'     => 100,
            'discount'       => 0,
            'vat_rate'       => 20,
            'invoice_status' => 1,
        ]);

        return $invoice->fresh();
    }
}
