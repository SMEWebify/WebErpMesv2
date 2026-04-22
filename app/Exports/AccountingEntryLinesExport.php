<?php

namespace App\Exports;

use App\Models\Accounting\AccountingEntry;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class AccountingEntryLinesExport implements FromCollection, WithHeadings, WithMapping
{
    private $ids;

    public function __construct($ids)
    {
        $this->ids = $ids;
    }

    // En-têtes officiels DGFiP (arrêté du 29 juillet 2013)
    public function headings(): array
    {
        return [
            'JournalCode',
            'JournalLib',
            'EcritureNum',
            'EcritureDate',
            'CompteNum',
            'CompteLib',
            'CompAuxNum',
            'CompAuxLib',
            'PieceRef',
            'PieceDate',
            'EcritureLib',
            'Debit',
            'Credit',
            'EcritureLet',
            'DateLet',
            'ValidDate',
            'Montantdevise',
            'Idevise',
        ];
    }

    public function map($e): array
    {
        return [
            $e->journal_code,
            $e->journal_label,
            $e->sequence_number,
            $e->accounting_date->format('Ymd'),
            $e->account_number,
            $e->account_label,
            $e->auxiliary_account_number ?? '',
            $e->auxiliary_account_label  ?? '',
            $e->justification_reference  ?? '',
            $e->justification_date?->format('Ymd') ?? '',
            $e->entry_label,
            number_format((float) $e->debit_amount,  2, ',', ''),
            number_format((float) $e->credit_amount, 2, ',', ''),
            $e->entry_lettering  ?? '',
            $e->lettering_date?->format('Ymd') ?? '',
            $e->validation_date->format('Ymd'),
            '', // Montantdevise — vide si monnaie fonctionnelle
            '', // Idevise
        ];
    }

    public function collection()
    {
        return AccountingEntry::whereIn('id', $this->ids)->get();
    }
}
