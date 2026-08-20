<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CreditNoteKPIService
{
    /**
     * Retrieves the rate of grouped credit notes by status.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCreditNotesDataRate()
    {
        return DB::table('credit_notes')
                    ->select('statu', DB::raw('count(*) as CreditNotesCountRate'))
                    ->groupBy('statu')
                    ->get();
    }

    /**
     * Get a monthly recap of credit notes for a given year.
     *
     * This function retrieves the monthly summary of credit notes for the specified year.
     * It joins the `credit_note_lines` table with the `order_lines` table to calculate
     * the total sum of credit notes for each month, considering the selling price, quantity,
     * and discount of the order lines.
     *
     * @param int $year The year for which to retrieve the credit notes monthly recap.
     * @return \Illuminate\Support\Collection A collection of objects containing the month and the total sum of credit notes for that month.
     */
    public function getCreditNotesMonthlyRecap($year)
    {
        // LEFT JOIN : un avoir peut porter sur une ligne de facture libre, sans
        // ligne de commande. Le prix figé sur la ligne d'avoir fait foi.
        return DB::table('credit_note_lines')
                    ->leftJoin('order_lines', 'credit_note_lines.order_line_id', '=', 'order_lines.id')
                    ->selectRaw('
                        MONTH(credit_note_lines.created_at) AS month,
                        SUM(COALESCE(credit_note_lines.unit_price, order_lines.selling_price, 0) * credit_note_lines.qty
                            * (1 - COALESCE(credit_note_lines.discount, order_lines.discount, 0)/100)) AS orderSum
                    ')
                    ->whereYear('credit_note_lines.created_at', $year)
                    ->groupByRaw('MONTH(credit_note_lines.created_at) ')
                    ->get();
    }

}
