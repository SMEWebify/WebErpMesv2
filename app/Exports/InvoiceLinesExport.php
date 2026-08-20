<?php

namespace App\Exports;

use App\Models\Workflow\InvoiceLines;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class InvoiceLinesExport implements FromCollection , WithHeadings, WithMapping
{
    
    private $InvoiceLineId;

    Public function __construct($InvoiceLineId)
    {
        $this->InvoiceLineId = $InvoiceLineId;
    }

    public function headings(): array
    {
        return [
            'INVOICE_CODE',
            'ORDER_CODE',
            'DESCRIPTION',
            'QTY',
            'UNIT',
            'PRICE',
            'DISCOUNT',
            'VAT RATE',
        ];
    }

    public function map($invoiceLine): array
    {
        // Une ligne libre n'a pas de commande d'origine : tout vient du snapshot
        // porté par la ligne de facture.
        return [
            $invoiceLine->invoice->code,
            $invoiceLine->orderLine?->order?->code,
            $invoiceLine->display_label,
            $invoiceLine->qty,
            $invoiceLine->display_unit_label,
            $invoiceLine->resolved_unit_price,
            $invoiceLine->resolved_discount,
            $invoiceLine->resolved_vat_rate,
        ];
    }

    public function collection()
    {
        return InvoiceLines::whereIn('id', $this->InvoiceLineId)->get();
        
    }
}