<?php

namespace App\Services;

use App\Models\Workflow\OrderConfirmations;

/**
 * Totaux d'un ARC.
 *
 * Même interface que OrderCalculatorService (getTotalPrice / getSubTotal / getVatTotal)
 * pour rester compatible avec PrintController::generatePdf, mais les montants sont
 * calculés sur les valeurs figées des lignes d'ARC. Aucune lecture de la commande :
 * un ARC réimprimé deux ans plus tard doit afficher les mêmes montants qu'à l'envoi.
 */
class OrderConfirmationCalculatorService
{
    private $confirmation;

    public function __construct(OrderConfirmations $confirmation)
    {
        $this->confirmation = $confirmation;
    }

    /**
     * Ventilation de la TVA par taux.
     *
     * Regroupée sur le taux figé et non sur accounting_vats_id : le taux d'un
     * enregistrement de TVA peut être modifié après l'envoi de l'ARC.
     *
     * @return array Tableau [taux => [taux, montant de TVA]]
     */
    public function getVatTotal()
    {
        $tableauTVA = array();

        foreach ($this->confirmation->OrderConfirmationLines as $line) {
            $vatRate = (float) ($line->vat_rate ?? 0);
            $key = (string) $vatRate;

            $TotalCurentLine = $this->lineSubTotal($line);
            $TotalVATCurentLine = $TotalCurentLine * ($vatRate / 100);

            if (array_key_exists($key, $tableauTVA)) {
                $tableauTVA[$key][1] += $TotalVATCurentLine;
            } else {
                $tableauTVA[$key] = array($vatRate, $TotalVATCurentLine);
            }
        }

        asort($tableauTVA);
        return $tableauTVA;
    }

    /**
     * Total TTC de l'ARC.
     *
     * @return float
     */
    public function getTotalPrice()
    {
        $TotalPrice = 0;

        foreach ($this->confirmation->OrderConfirmationLines as $line) {
            $vatRate = (float) ($line->vat_rate ?? 0);
            $TotalPriceLine = $this->lineSubTotal($line);
            $TotalPrice += $TotalPriceLine + ($TotalPriceLine * ($vatRate / 100));
        }

        return $TotalPrice;
    }

    /**
     * Total HT de l'ARC.
     *
     * @return float
     */
    public function getSubTotal()
    {
        $SubTotal = 0;

        foreach ($this->confirmation->OrderConfirmationLines as $line) {
            $SubTotal += round($this->lineSubTotal($line), 2);
        }

        return $SubTotal;
    }

    /**
     * Total HT d'une ligne, remise déduite.
     *
     * @param \App\Models\Workflow\OrderConfirmationLines $line
     * @return float
     */
    private function lineSubTotal($line)
    {
        $base = (float) $line->qty * (float) $line->selling_price;

        return $base - $base * (((float) ($line->discount ?? 0)) / 100);
    }
}
