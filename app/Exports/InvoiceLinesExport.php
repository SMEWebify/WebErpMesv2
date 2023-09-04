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
        return [
            $invoiceLine->invoice->code,
            $invoiceLine->orderLine->order->code,
            $invoiceLine->orderLine->label,
            $invoiceLine->qty,
            $invoiceLine->orderLine->Unit->label,
            $invoiceLine->orderLine->selling_price,
            $invoiceLine->orderLine->discount,
            $invoiceLine->orderLine->vat->rate,
        ];
    }

    public function collection()
    {
        return InvoiceLines::whereIn('id', $this->InvoiceLineId)->get();
        
    }
}