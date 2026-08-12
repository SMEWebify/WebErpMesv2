<?php

namespace App\Services\Invoicing;

use App\Models\Workflow\Invoices;
use App\Services\InvoiceCalculatorService;
use App\Services\PdfThemeResolver;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Number;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdDocumentPdfBuilder;
use horstoeko\zugferd\ZugferdProfiles;

/**
 * Génère le Factur-X (profil EN 16931) d'une facture WEM et le **retourne en
 * mémoire**, sans écrire sur disque ni produire de réponse HTTP.
 *
 * C'est la source unique du document électronique : PrintController s'en sert
 * pour le téléchargement, les drivers PDP (cf. App\Services\Integrations\Pdp)
 * pour le dépôt sur la plateforme. Les deux envoient donc rigoureusement le
 * même document, ce qui est la condition pour que le PDF archivé par le client
 * et celui reçu par son acheteur soient identiques.
 *
 * Deux représentations du même contenu :
 *   - buildPdf() : PDF/A-3 avec le XML CII en pièce jointe (Factur-X complet)
 *   - buildXml() : le XML CII seul, suffisant pour la pré-validation
 */
class FacturXBuilder
{
    public function __construct(private PdfThemeResolver $themeResolver) {}

    /** Factur-X complet : PDF/A-3 lisible + XML CII embarqué. */
    public function buildPdf(Invoices $invoice): string
    {
        $document = $this->documentBuilder($invoice);

        // Le PDF lisible doit être rendu après la construction des données : le
        // rendu de la vue déplace les lignes sur $invoice->Lines (cf. renderReadablePdf).
        $pdfBuilder = new ZugferdDocumentPdfBuilder($document, $this->renderReadablePdf($invoice));
        $pdfBuilder->generateDocument();

        return $pdfBuilder->downloadString();
    }

    /** XML CII seul (EN 16931), sans le PDF lisible. */
    public function buildXml(Invoices $invoice): string
    {
        return $this->documentBuilder($invoice)->getContent();
    }

    /**
     * Construit les données structurées EN 16931 de la facture.
     *
     * Toutes les valeurs monétaires proviennent du snapshot figé sur
     * invoice_lines (InvoiceCalculatorService), la même source que le PDF :
     * un changement de tarif après émission ne peut donc pas modifier
     * rétroactivement un document déjà déposé.
     */
    private function documentBuilder(Invoices $invoice): ZugferdDocumentBuilder
    {
        $this->assertIssuable($invoice);

        $factory    = app('Factory');
        $currency   = $factory->curency ?? 'EUR';
        $calculator = new InvoiceCalculatorService($invoice);

        $client = $invoice->companie;
        // Adresse de facturation : celle liée à la facture en priorité, sinon
        // l'adresse par défaut du client, sinon la première disponible.
        $clientAddress = $invoice->adresse
            ?? $client->Addresses()->where('default', 1)->first()
            ?? $client->Addresses()->first();

        $this->assertPartiesAreIdentifiable($invoice, $factory, $client, $clientAddress);

        $issueDate = $invoice->created_at instanceof \DateTimeInterface
            ? $invoice->created_at
            : \Carbon\Carbon::parse($invoice->created_at);

        $vatBreakdown = $calculator->getVatBreakdown();
        $lines        = $calculator->getNormalizedLines();
        $totalVAT     = round(array_sum(array_column($vatBreakdown, 'vat')), 2);
        $totalPrice   = $calculator->getTotalPrice();
        $subPrice     = $calculator->getSubTotal();

        $zugferddatas = ZugferdDocumentBuilder::CreateNew(ZugferdProfiles::PROFILE_EN16931);
        $zugferddatas
        ->setDocumentInformation($invoice->code, $this->documentTypeCode($invoice), $issueDate, $currency)
        ->addDocumentNote('Facture ' . $invoice->code . ' du ' . $issueDate->format('d/m/Y'))

        // Référence acheteur (BT-10) : référence commande/marché côté client.
        ->setDocumentBuyerReference($invoice->customer_reference ?: $invoice->code)

        // Ajout des informations du vendeur (Factory)
        ->setDocumentSeller($factory->name)
        ->setDocumentSellerLegalOrganisation($factory->siren, '0002', $factory->name)
        ->addDocumentSellerTaxRegistration('VA', $factory->vat_num)
        ->setDocumentSellerAddress(
            $factory->address,
            null,
            null,
            $factory->zipcode,
            $factory->city,
            $this->countryCode($factory->country)
        )
        ->setDocumentSellerContact(
            $factory->name,
            null,
            $factory->phone_number,
            null,
            $factory->mail
        )

        // Ajout des informations du client
        ->setDocumentBuyer($client->label, $client->code)
        ->setDocumentBuyerAddress(
            $clientAddress->adress ?? 'N/A',
            null,
            null,
            $clientAddress->zipcode ?? '00000',
            $clientAddress->city ?? 'N/A',
            $this->countryCode($clientAddress->country ?? null)
        );

        if ($client->siren) {
            $zugferddatas->setDocumentBuyerLegalOrganisation($client->siren, '0002', $client->label);
        }
        if ($client->intra_community_vat) {
            $zugferddatas->addDocumentBuyerTaxRegistration('VA', $client->intra_community_vat);
        }

        // Adresses électroniques de facturation (BT-34 vendeur, BT-49 acheteur).
        // C'est sur elles que la plateforme route le document : sans BT-49, elle
        // ne sait pas à qui remettre la facture et refuse le dépôt.
        if ($sellerAddress = $this->electronicAddress($factory)) {
            $zugferddatas->setDocumentSellerCommunication($sellerAddress['scheme'], $sellerAddress['value']);
        }
        if ($buyerAddress = $this->electronicAddress($client)) {
            $zugferddatas->setDocumentBuyerCommunication($buyerAddress['scheme'], $buyerAddress['value']);
        }

        // Moyen et conditions de paiement
        if ($factory->iban) {
            $zugferddatas->addDocumentPaymentMeanToCreditTransfer(
                $factory->iban,
                $factory->name,
                null,
                $factory->bic ?: null
            );
        }
        if ($invoice->due_date) {
            $dueDate = \Carbon\Carbon::parse($invoice->due_date);
            $zugferddatas->addDocumentPaymentTerm('Échéance le ' . $dueDate->format('d/m/Y'), $dueDate);
        }

        // Ventilation de la TVA (BG-23) : une ligne par taux, base + montant.
        foreach ($vatBreakdown as $vat) {
            $category = $vat['rate'] > 0 ? 'S' : 'Z'; // S = taux standard, Z = taux zéro
            $zugferddatas->addDocumentTax($category, 'VAT', round($vat['base'], 2), round($vat['vat'], 2), $vat['rate']);
        }

        // Totaux : grand total, dû, total lignes, charges, remises, base TVA, TVA, arrondi, payé.
        $zugferddatas->setDocumentSummation(
            round($totalPrice, 2),
            round($invoice->remaining_amount ?? $totalPrice, 2),
            round($subPrice, 2),
            0.0,
            0.0,
            round($subPrice, 2),
            $totalVAT,
            0.0,
            round($invoice->paid_amount ?? 0, 2)
        );

        // Lignes de facture (snapshot des prix, remise et TVA figés)
        foreach ($lines as $key => $line) {
            $zugferddatas->addNewPosition($key + 1)
                ->setDocumentPositionProductDetails($line['label'] ?: $line['code'], null, $line['code'])
                ->setDocumentPositionGrossPrice(round($line['unit_price'], 2));

            if ($line['discount'] > 0) {
                $zugferddatas->addDocumentPositionGrossPriceAllowanceCharge(
                    round($line['unit_price'] * $line['discount'] / 100, 2),
                    false, // remise (allowance), pas une charge
                    $line['discount'],
                    round($line['unit_price'], 2),
                    'Remise'
                );
            }

            $zugferddatas->setDocumentPositionNetPrice(round($line['net_unit_price'], 2))
                ->setDocumentPositionQuantity($line['qty'], $this->unitCode($line['unit_code']))
                ->addDocumentPositionTax($line['vat_rate'] > 0 ? 'S' : 'Z', 'VAT', $line['vat_rate'])
                ->setDocumentPositionLineSummation(round($line['line_total'], 2));
        }

        return $zugferddatas;
    }

    /**
     * Rend le PDF lisible (« human readable view ») qui portera le XML.
     *
     * Effet de bord assumé et hérité du rendu historique : la vue attend les
     * lignes sur $invoice->Lines, pas sur la relation invoiceLines.
     */
    private function renderReadablePdf(Invoices $invoice): string
    {
        $factory    = app('Factory');
        $currency   = $factory->curency ?? 'EUR';
        $calculator = new InvoiceCalculatorService($invoice);

        $typeDocumentName    = __('general_content.invoice_trans_key');
        $formattedTotalPrice = Number::currency($calculator->getTotalPrice(), $currency, config('app.locale'));
        $formattedSubPrice   = Number::currency($calculator->getSubTotal(), $currency, config('app.locale'));
        $vatPrice            = $calculator->getVatTotal();
        $normalizeCurrency   = fn ($value) => str_replace(["\u{00A0}", "\u{202F}"], ' ', (string) $value);

        $invoice->Lines = $invoice->invoiceLines;
        unset($invoice->invoiceLines);

        $Document  = $invoice;
        $Factory   = $factory;
        $image     = $factory->getImageFactoryPath();
        $customCss = $factory->pdf_custom_css;
        $view      = $this->themeResolver->resolveForDocument($invoice, 'print/pdf-invoice', $factory);

        return PDF::loadView($view, compact(
            'typeDocumentName', 'Document', 'Factory', 'image',
            'formattedTotalPrice', 'formattedSubPrice', 'vatPrice',
            'customCss', 'normalizeCurrency'
        ))->stream();
    }

    /**
     * Refuse de produire un document électronique pour une facture qui n'est
     * pas émise. Un Factur-X porte un type UNTDID 380 et un numéro : le sortir
     * d'un brouillon (encore renumérotable, encore supprimable) ou d'une
     * proforma (document commercial, sans valeur comptable) fabrique une
     * facture qui n'existe pas.
     *
     * La garde est ici et non dans le contrôleur parce que le dépôt PDP
     * (SuperPdpGateway::submit) appelle buildPdf() sans passer par HTTP.
     */
    private function assertIssuable(Invoices $invoice): void
    {
        if ((int) $invoice->statu === 1) {
            throw new \RuntimeException(__('general_content.invoice_draft_no_facturx_trans_key'));
        }

        if ((int) $invoice->invoice_type === 3) {
            throw new \RuntimeException(__('general_content.invoice_proforma_no_facturx_trans_key'));
        }
    }

    /**
     * Vérifie que les deux parties sont identifiables avant de construire le
     * document.
     *
     * Sans ce contrôle, une facture adressée à une société de démonstration
     * part jusqu'à la plateforme et revient sous forme de règle schematron
     * («\u{00A0}[BR-CO-09] The Seller VAT identifier shall have a prefix…\u{00A0}»),
     * message exact mais illisible pour l'utilisateur, et qui ne dit ni quelle
     * société ni quel champ corriger. On préfère échouer ici, en français, en
     * énumérant tout ce qui manque d'un coup plutôt qu'un problème par essai.
     *
     * @throws \RuntimeException si une donnée obligatoire manque ou est invalide
     */
    private function assertPartiesAreIdentifiable(Invoices $invoice, object $factory, ?object $client, ?object $clientAddress): void
    {
        $problems = [];

        // Vendeur — paramètres de la société (Administration → Société).
        if (blank($factory->siren)) {
            $problems[] = 'Votre société n\'a pas de SIREN (BT-30).';
        }
        if (blank($factory->vat_num)) {
            $problems[] = 'Votre société n\'a pas de numéro de TVA intracommunautaire (BT-31).';
        } elseif (! $this->looksLikeVatNumber($factory->vat_num)) {
            $problems[] = "Le numéro de TVA de votre société («\u{00A0}{$factory->vat_num}\u{00A0}») doit commencer par le code pays, par exemple FR12345678901 (BT-31).";
        }
        if (blank($factory->address) || blank($factory->zipcode) || blank($factory->city)) {
            $problems[] = 'L\'adresse postale de votre société est incomplète : rue, code postal et ville sont obligatoires (BG-5).';
        }

        // Acheteur — fiche société du client.
        if (! $client) {
            $problems[] = 'La facture n\'est rattachée à aucun client.';
        } else {
            $name = $client->label ?: "#{$client->id}";

            if (! $clientAddress) {
                $problems[] = "Le client «\u{00A0}{$name}\u{00A0}» n'a aucune adresse postale (BG-8).";
            }
            if (filled($client->intra_community_vat) && ! $this->looksLikeVatNumber($client->intra_community_vat)) {
                $problems[] = "Le numéro de TVA du client «\u{00A0}{$name}\u{00A0}» («\u{00A0}{$client->intra_community_vat}\u{00A0}») doit commencer par le code pays, par exemple FR12345678901 (BT-48).";
            }
            if (! $this->electronicAddress($client)) {
                $problems[] = "Le client «\u{00A0}{$name}\u{00A0}» n'a ni adresse électronique de facturation ni SIREN : la plateforme ne saurait pas à qui remettre la facture (BT-49).";
            }
        }

        if ($problems !== []) {
            throw new \RuntimeException(
                "La facture {$invoice->code} ne peut pas être émise en facturation électronique :\n"
                . '— ' . implode("\n— ", $problems)
            );
        }
    }

    /**
     * Un numéro de TVA doit porter un préfixe pays ISO 3166-1 alpha-2 (BR-CO-09),
     * la Grèce pouvant utiliser « EL ». On ne valide pas la clé de contrôle :
     * c'est le rôle du validateur de la plateforme, pas le nôtre.
     */
    private function looksLikeVatNumber(?string $value): bool
    {
        return (bool) preg_match('/^[A-Z]{2}[0-9A-Z]{2,}$/i', preg_replace('/\s+/', '', (string) $value));
    }

    /**
     * Adresse électronique de facturation d'une partie (BT-34 / BT-49).
     *
     * Repli sur le SIREN quand l'adresse n'est pas renseignée : c'est le choix
     * que fait la majorité des entreprises françaises, et la valeur par défaut
     * recommandée pour ouvrir une ligne d'annuaire. Le repli ne s'applique pas
     * hors du référentiel français (scheme 0225), où le SIREN n'a pas de sens.
     *
     * @return array{scheme: string, value: string}|null
     */
    private function electronicAddress(object $party): ?array
    {
        $scheme = trim((string) ($party->electronic_address_scheme ?? '')) ?: '0225';
        $value  = trim((string) ($party->electronic_address ?? ''));

        if ($value === '' && $scheme === '0225') {
            $value = trim((string) ($party->siren ?? ''));
        }

        return $value !== '' ? ['scheme' => $scheme, 'value' => $value] : null;
    }

    /** Type de document (UNTDID 1001) selon le type de facture interne. */
    private function documentTypeCode(Invoices $invoice): string
    {
        return [
            1 => '380', // facture
            2 => '381', // avoir
            3 => '380', // proforma (traité comme facture)
            4 => '386', // facture d'acompte
        ][$invoice->invoice_type] ?? '380';
    }

    /**
     * Normalise un pays en code ISO 3166-1 alpha-2 (requis EN 16931, BT-40/BT-55).
     *
     * Accepte déjà un code à 2 lettres, sinon convertit quelques libellés
     * courants. Faute de correspondance, retombe sur 'FR' (ERP français).
     */
    public function countryCode(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 'FR';
        }

        if (preg_match('/^[A-Za-z]{2}$/', $value)) {
            return strtoupper($value);
        }

        $map = [
            'france' => 'FR', 'belgique' => 'BE', 'belgium' => 'BE',
            'allemagne' => 'DE', 'germany' => 'DE', 'espagne' => 'ES', 'spain' => 'ES',
            'italie' => 'IT', 'italy' => 'IT', 'suisse' => 'CH', 'switzerland' => 'CH',
            'luxembourg' => 'LU', 'pays-bas' => 'NL', 'netherlands' => 'NL',
            'portugal' => 'PT', 'royaume-uni' => 'GB', 'united kingdom' => 'GB',
        ];

        return $map[mb_strtolower($value)] ?? 'FR';
    }

    /**
     * Convertit l'unité interne en code unité UN/ECE Rec. 20 (EN 16931, BT-130).
     * Par défaut 'C62' (unité/pièce), code générique recommandé par la norme.
     */
    public function unitCode(?string $code): string
    {
        $map = [
            'pcs' => 'C62', 'pc' => 'C62', 'u' => 'C62', 'unite' => 'C62', 'piece' => 'C62',
            'kg' => 'KGM', 'g' => 'GRM', 't' => 'TNE',
            'm' => 'MTR', 'ml' => 'MTR', 'm2' => 'MTK', 'm²' => 'MTK', 'm3' => 'MTQ', 'm³' => 'MTQ',
            'l' => 'LTR', 'h' => 'HUR', 'heure' => 'HUR',
        ];

        return $map[mb_strtolower(trim((string) $code))] ?? 'C62';
    }
}
