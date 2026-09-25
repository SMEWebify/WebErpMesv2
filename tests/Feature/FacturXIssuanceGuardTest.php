<?php

namespace Tests\Feature;

use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\User;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Services\Invoicing\FacturXBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le Factur-X ne se produit que sur une facture réellement émise.
 *
 * Un brouillon est encore renumérotable et supprimable, une proforma n'a pas de
 * valeur comptable : en sortir un document de type UNTDID 380 fabriquerait une
 * facture qui n'existe pas. La garde est dans le builder et non dans le
 * contrôleur, parce que le dépôt PDP appelle buildPdf() sans passer par HTTP.
 *
 * Les identités vendeur et acheteur sont complètes dans tous les cas : ce sont
 * bien le statut et le type de document qui sont testés, pas le contrôle
 * d'identifiabilité des parties (cf. assertPartiesAreIdentifiable).
 */
class FacturXIssuanceGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_draft_invoice_cannot_produce_a_facturx(): void
    {
        $invoice = $this->makeInvoice(['statu' => 1, 'invoice_type' => 1]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(__('general_content.invoice_draft_no_facturx_trans_key'));

        $this->app->make(FacturXBuilder::class)->buildXml($invoice);
    }

    public function test_a_proforma_cannot_produce_a_facturx(): void
    {
        $invoice = $this->makeInvoice(['statu' => 2, 'invoice_type' => 3]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(__('general_content.invoice_proforma_no_facturx_trans_key'));

        $this->app->make(FacturXBuilder::class)->buildXml($invoice);
    }

    public function test_an_issued_invoice_still_produces_a_facturx(): void
    {
        $invoice = $this->makeInvoice(['statu' => 2, 'invoice_type' => 1]);

        $xml = $this->app->make(FacturXBuilder::class)->buildXml($invoice);

        $this->assertStringContainsString('<ram:TypeCode>380</ram:TypeCode>', $xml);
    }

    /**
     * Un client sans SIREN bloque l'émission : l'écran doit afficher les
     * informations à compléter, pas une erreur 500.
     */
    public function test_a_client_without_siren_shows_what_to_fix_instead_of_a_500(): void
    {
        $invoice = $this->makeInvoice(['statu' => 2, 'invoice_type' => 1]);
        $invoice->companie->forceFill(['siren' => null, 'intra_community_vat' => null])->save();

        $this->actingAs(User::factory()->create())
            ->get(route('pdf.facturex', ['Document' => $invoice->id]))
            ->assertStatus(422)
            ->assertSee('ni adresse électronique de facturation ni SIREN', false)
            ->assertSee(route('companies.show', ['id' => $invoice->companies_id]), false);
    }

    /* ------------------------------------------------------------- Utilitaires */

    /**
     * TestCase crée déjà une société « Test Factory », et le singleton résout
     * Factory::first() : il faut renseigner CETTE ligne, pas en ajouter une
     * seconde qui ne serait jamais lue.
     */
    private function makeFactory(): void
    {
        $factory = Factory::first() ?? new Factory();

        $factory->fill([
            'name'    => 'Burger Queen',
            'siren'   => '853322915',
            'vat_num' => 'FR18853322915',
            'address' => '809 avenue du Languedoc',
            'zipcode' => '12100',
            'city'    => 'Millau',
            'country' => 'FR',
            'curency' => 'EUR',
        ])->save();

        $this->app->forgetInstance('Factory');
    }

    private function makeInvoice(array $attributes): Invoices
    {
        $this->makeFactory();

        $client = Companies::factory()->create([
            'label'               => 'Tricatel',
            'statu_customer'      => 2,
            'siren'               => '552081317',
            'intra_community_vat' => 'FR15552081317',
        ]);

        $invoice = Invoices::factory()->create($attributes + [
            'code'         => 'FA-2026-777',
            'companies_id' => $client->id,
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
