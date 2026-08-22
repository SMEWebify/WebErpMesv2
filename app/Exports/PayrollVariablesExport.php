<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Neutral payroll variable elements file.
 *
 * One line per employee and per pay item. Payroll editors each expect their own
 * layout, so the aim here is the smallest common denominator every one of them
 * can ingest: adapting to Silae, Sage or Cegid is a mapping of the Code column,
 * not another export class.
 */
class PayrollVariablesExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $rows)
    {
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Matricule',
            'Salarie',
            'PeriodeDebut',
            'PeriodeFin',
            'Code',
            'Libelle',
            'Quantite',
            'Unite',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, string>
     */
    public function map($row): array
    {
        return [
            $row['matricule'],
            $row['name'],
            $row['period_start'],
            $row['period_end'],
            $row['code'],
            $row['label'],
            // Comma as the decimal separator, like the FEC export: French
            // payroll imports expect it and Excel FR reads it as a number.
            number_format((float) $row['quantity'], 2, ',', ''),
            $row['unit'],
        ];
    }

    public function collection(): Collection
    {
        return $this->rows;
    }
}
