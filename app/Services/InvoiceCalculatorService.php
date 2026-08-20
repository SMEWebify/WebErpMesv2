<?php
namespace App\Services;

use App\Models\Workflow\Invoices;

class InvoiceCalculatorService
{
    private $invoices;

    public $TotalPrice;
    public $SubTotal;
    public $VatTotal;

    public function __construct(Invoices $invoices)
    {
        $this->invoices = $invoices;
    }

    /**
     * Résout le prix unitaire, remise et taux TVA pour une ligne.
     * Utilise le snapshot stocké sur invoice_lines en priorité ;
     * repli sur orderLine pour les lignes antérieures à la migration.
     *
     * Les lignes libres (frais de port, prestation ponctuelle) n'ont pas de
     * ligne de commande : tout vient alors du snapshot.
     */
    private function lineSnapshot($invoicesLine): array
    {
        $unitPrice = $invoicesLine->resolved_unit_price;
        $discount  = $invoicesLine->resolved_discount;
        $vatRate   = $invoicesLine->resolved_vat_rate;

        // Clé de regroupement de la ventilation TVA. Une ligne libre peut ne
        // porter aucun identifiant de TVA : on retombe alors sur le taux, pour
        // ne pas agréger toutes ces lignes sous une clé vide.
        $vatId = $invoicesLine->resolved_vat_id ?? 'rate-' . number_format((float) $vatRate, 3, '.', '');

        return [$unitPrice, $discount, $vatRate, $vatId];
    }

    public function getVatTotal()
    {
        $tableauTVA    = [];
        $invoicesLines = $this->invoices->invoiceLines;

        foreach ($invoicesLines as $invoicesLine) {
            [$unitPrice, $discount, $vatRate, $vatId] = $this->lineSnapshot($invoicesLine);

            $subtotalLine  = $invoicesLine->qty * $unitPrice * (1 - $discount / 100);
            $vatAmountLine = $subtotalLine * ($vatRate / 100);

            if (array_key_exists($vatId, $tableauTVA)) {
                $tableauTVA[$vatId][1] += $vatAmountLine;
            } else {
                $tableauTVA[$vatId] = [$vatRate, $vatAmountLine];
            }
        }

        asort($tableauTVA);
        return $tableauTVA;
    }

    public function getTotalPrice()
    {
        $TotalPrice    = 0;
        $invoicesLines = $this->invoices->invoiceLines;

        foreach ($invoicesLines as $invoicesLine) {
            [$unitPrice, $discount, $vatRate] = $this->lineSnapshot($invoicesLine);

            $subtotalLine = $invoicesLine->qty * $unitPrice * (1 - $discount / 100);
            $TotalPrice  += $subtotalLine + $subtotalLine * ($vatRate / 100);
        }

        return $TotalPrice;
    }

    public function getSubTotal()
    {
        $SubTotal      = 0;
        $invoicesLines = $this->invoices->invoiceLines;

        foreach ($invoicesLines as $invoicesLine) {
            [$unitPrice, $discount] = $this->lineSnapshot($invoicesLine);

            $SubTotal += round($invoicesLine->qty * $unitPrice * (1 - $discount / 100), 2);
        }

        return $SubTotal;
    }

    /**
     * Ventilation de la TVA par taux, à partir du snapshot des lignes.
     *
     * Contrairement à getVatTotal() (regroupé par identifiant de TVA), cette
     * méthode renvoie pour chaque taux la base imposable ET le montant de TVA,
     * ce qui est requis par la norme EN 16931 (BG-23 : VAT breakdown).
     *
     * @return array<string,array{rate:float,base:float,vat:float}>
     */
    public function getVatBreakdown(): array
    {
        $breakdown = [];

        foreach ($this->invoices->invoiceLines as $invoicesLine) {
            [$unitPrice, $discount, $vatRate] = $this->lineSnapshot($invoicesLine);

            $base = $invoicesLine->qty * $unitPrice * (1 - $discount / 100);
            $vat  = $base * ($vatRate / 100);
            $key  = number_format((float) $vatRate, 3, '.', '');

            if (!isset($breakdown[$key])) {
                $breakdown[$key] = ['rate' => (float) $vatRate, 'base' => 0.0, 'vat' => 0.0];
            }

            $breakdown[$key]['base'] += $base;
            $breakdown[$key]['vat']  += $vat;
        }

        return $breakdown;
    }

    /**
     * Lignes normalisées avec prix figés (snapshot), prix net après remise
     * et total de ligne. Sert de source unique pour le PDF et le Factur-X.
     *
     * @return array<int,array{label:string,code:string,qty:float,unit_price:float,discount:float,vat_rate:float,net_unit_price:float,line_total:float,unit_code:?string}>
     */
    public function getNormalizedLines(): array
    {
        $lines = [];

        foreach ($this->invoices->invoiceLines as $invoicesLine) {
            [$unitPrice, $discount, $vatRate] = $this->lineSnapshot($invoicesLine);

            $netUnitPrice = $unitPrice * (1 - $discount / 100);

            $lines[] = [
                'label'          => $invoicesLine->display_label,
                'code'           => $invoicesLine->display_code,
                'qty'            => (float) $invoicesLine->qty,
                'unit_price'     => (float) $unitPrice,
                'discount'       => (float) $discount,
                'vat_rate'       => (float) $vatRate,
                'net_unit_price' => $netUnitPrice,
                'line_total'     => $invoicesLine->qty * $netUnitPrice,
                'unit_code'      => $invoicesLine->display_unit_code,
            ];
        }

        return $lines;
    }
}
