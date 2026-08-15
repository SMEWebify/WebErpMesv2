<?php

namespace Tests\Feature;

use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Services\Invoicing\FacturXBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contrôle des identifiants avant construction du Factur-X.
 *
 * Sans lui, une facture adressée à une société de démonstration part jusqu'à la
 * plateforme et revient en règle schematron : message exact, mais qui ne dit ni
 * quelle société ni quel champ corriger.
 */
class FacturXPreflightTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_buyer_vat_without_country_prefix_is_refused(): void
    {
        // BR-CO-09 : c'est le cas réel des sociétés créées par les seeders,
        // dont la TVA est un simple entier.
        $invoice = $this->makeInvoice(['intra_community_vat' => '10']);

        $this->assertRefusedBecause($invoice, 'doit commencer par le code pays');
    }

    public function test_a_malformed_siren_is_refused(): void
    {
        // Un SIREN à un chiffre est émis avec le schéma 0002 (SIRENE) et sert
        // d'adresse d'acheminement par défaut : la facture n'arriverait nulle part.
        $invoice = $this->makeInvoice(['siren' => '3']);

        $this->assertRefusedBecause($invoice, 'doit comporter 9 chiffres');
    }

    // Pas de test « acheteur sans adresse postale » : le schéma l'interdit déjà.
    // invoices.companies_addresses_id est NOT NULL et la clé étrangère supprime
    // la facture en cascade avec l'adresse. Le contrôle correspondant reste dans
    // le builder par précaution, mais il n'est pas atteignable par ce chemin.

    public function test_a_buyer_without_any_routing_identifier_is_refused(): void
    {
        $invoice = $this->makeInvoice(['siren' => null, 'electronic_address' => null]);

        $this->assertRefusedBecause($invoice, 'ne saurait pas à qui remettre la facture');
    }

    public function test_an_incomplete_seller_is_refused(): void
    {
        $this->makeFactory(['siren' => null, 'vat_num' => 'FR18000000002']);

        $invoice = $this->makeInvoice([], seedFactory: false);

        $this->assertRefusedBecause($invoice, "Votre société n'a pas de SIREN");
    }

    public function test_every_problem_is_reported_at_once(): void
    {
        // Un problème par tentative transformerait la mise en conformité en
        // partie de devinettes.
        $invoice = $this->makeInvoice(['siren' => '3', 'intra_community_vat' => '10']);

        $message = $this->refusalMessage($invoice);

        $this->assertStringContainsString('BT-47', $message);
        $this->assertStringContainsString('BT-48', $message);
    }

    public function test_a_fully_identified_pair_builds_the_document(): void
    {
        $invoice = $this->makeInvoice([]);

        $this->assertStringContainsString('CrossIndustryInvoice', $this->builder()->buildXml($invoice));
    }

    /* ------------------------------------------------------------- Utilitaires */

    private function assertRefusedBecause(Invoices $invoice, string $needle): void
    {
        $this->assertStringContainsString($needle, $this->refusalMessage($invoice));
    }

    private function refusalMessage(Invoices $invoice): string
    {
        try {
            $this->builder()->buildXml($invoice);
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $this->fail('Le document a été construit alors que les identifiants sont invalides.');
    }

    private function builder(): FacturXBuilder
    {
        return $this->app->make(FacturXBuilder::class);
    }

    /** TestCase crée déjà une ligne « Test Factory » : c'est elle qu'on renseigne. */
    private function makeFactory(array $attributes = []): void
    {
        $factory = Factory::first() ?? new Factory();

        $factory->fill($attributes + [
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
    }

    private function makeInvoice(array $clientAttributes, bool $seedFactory = true): Invoices
    {
        if ($seedFactory) {
            $this->makeFactory();
        }

        $client = Companies::factory()->create($clientAttributes + [
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
            'statu'                  => 2, // émise : le builder refuse les brouillons
            'due_date'               => '2026-09-30',
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
