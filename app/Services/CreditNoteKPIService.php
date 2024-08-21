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

    public function getCreditNotesMonthlyRecap($year)
    {
        return DB::table('credit_note_lines')
                    ->join('order_lines', 'credit_note_lines.order_line_id', '=', 'order_lines.id')
                    ->selectRaw('
                        MONTH(credit_note_lines.created_at) AS month,
                        SUM((order_lines.selling_price * credit_note_lines.qty)-(order_lines.selling_price * credit_note_lines.qty)*(order_lines.discount/100)) AS orderSum
                    ')
                    ->whereYear('credit_note_lines.created_at', $year)
                    ->groupByRaw('MONTH(credit_note_lines.created_at) ')
                    ->get();
    }

}
