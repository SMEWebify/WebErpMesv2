<?php

namespace Tests\Feature;

use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Services\Integrations\Pdp\Inbound\FacturXReader;
use App\Services\Invoicing\FacturXBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Structure du conteneur Factur-X (PDF/A-3 + XML embarqué).
 *
 * Ce test existe à cause d'une panne silencieuse : la bibliothèque déclare la
 * pièce jointe en /AFRelationship /Data alors que la spécification impose
 * /Alternative. Le PDF restait parfaitement valide, notre propre lecteur le
 * relisait, le validateur de la plateforme l'acceptait — mais la chaîne
 * d'ingestion n'y trouvait aucune facture et la déposait sans montant ni
 * ligne, sans la moindre erreur. Rien, côté WEM, ne pouvait le signaler.
 */
class FacturXAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_embedded_xml_is_declared_as_an_alternative_representation(): void
    {
        $pdf = $this->buildPdf();

        $this->assertMatchesRegularExpression('#/AFRelationship\s*/Alternative#', $pdf);
        $this->assertStringNotContainsString('/AFRelationship /Data', $pdf);
    }

    public function test_the_attachment_keeps_the_name_expected_by_the_specification(): void
    {
        $this->assertStringContainsString('factur-x.xml', $this->buildPdf());
    }

    public function test_the_delivery_block_is_never_left_empty(): void
    {
        // Cause réelle du rejet de quatre factures : la bibliothèque émet
        // toujours le conteneur ApplicableHeaderTradeDelivery, vide si on ne le
        // renseigne pas. Un élément vide viole PEPPOL-EN16931-R008, et la
        // plateforme rejette le document dessus (fr:213, motif REJ_SEMAN) —
        // plusieurs heures après le dépôt, sans que rien ne l'ait laissé prévoir.
        $xml = $this->app->make(FacturXBuilder::class)->buildXml($this->makeInvoice());

        $this->assertStringNotContainsString('<ram:ApplicableHeaderTradeDelivery/>', $xml);
        $this->assertMatchesRegularExpression('#<ram:ShipToTradeParty>.*?<ram:CountryID>FR</ram:CountryID>#s', $xml);
    }

    public function test_the_document_can_be_read_back(): void
    {
        // Un aller-retour complet : ce que nous émettons doit être lisible par
        // le même lecteur que celui appliqué aux factures fournisseurs reçues.
        $data = $this->app->make(FacturXReader::class)->read($this->buildPdf());

        $this->assertSame('FA-2026-042', $data->invoiceNumber);
        $this->assertSame('Burger Queen', $data->sellerName);
        $this->assertSame(200.0, $data->totalHt);
        $this->assertSame(240.0, $data->totalTtc);
        $this->assertCount(1, $data->lines);
    }

    /* ------------------------------------------------------------- Utilitaires */

    private function buildPdf(): string
    {
        return $this->app->make(FacturXBuilder::class)->buildPdf($this->makeInvoice());
    }

    private function makeInvoice(): Invoices
    {
        // TestCase crée déjà une ligne « Test Factory » : c'est elle qu'on renseigne.
        ($factory = Factory::first() ?? new Factory())->fill([
            'name'               => 'Burger Queen',
            'siren'              => '000000002',
            'vat_num'            => 'FR18000000002',
            'electronic_address' => '315143296_59359',
            'address'            => '809 avenue du Languedoc',
            'zipcode'            => '12100',
            'city'               => 'Millau',
            'country'            => 'FR',
            'curency'            => 'EUR',
        ])->save();

        $this->app->forgetInstance('Factory');

        $client = Companies::factory()->create([
            'label'               => 'Tricatel',
            'statu_customer'      => 2,
            'siren'               => '000000001',
            'intra_community_vat' => 'FR15000000001',
            'electronic_address'  => '315143296_59358',
        ]);

        $address = CompaniesAddresses::create([
            'companies_id' => $client->id,
            'ordre'        => 1,
            'label'        => 'Siège',
            'adress'       => '12 rue de la Gastronomie',
            'zipcode'      => '12100',
            'city'         => 'Millau',
            'country'      => 'FR',
            'default'      => 1,
        ]);

        $invoice = Invoices::factory()->create([
            'code'                   => 'FA-2026-042',
            'companies_id'           => $client->id,
            'companies_addresses_id' => $address->id,
            'invoice_type'           => 1,
            'statu'                  => 2,
            'due_date'               => '2026-09-30',
        ]);

        InvoiceLines::create([
            'invoices_id'    => $invoice->id,
            'order_line_id'  => OrderLines::factory()->create(['code' => 'PART-1', 'label' => 'Tôle pliée 3mm'])->id,
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
