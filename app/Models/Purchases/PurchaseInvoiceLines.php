<?php

namespace App\Models\Purchases;

use App\Models\Purchases\PurchaseLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Purchases\PurchaseInvoice;
use App\Models\Accounting\AccountingEntry;
use App\Models\Purchases\PurchaseReceiptLines;

class PurchaseInvoiceLines extends Model
{
    protected $fillable = [
        
        'purchase_invoice_id',
        'purchase_receipt_line_id',
        'purchase_line_id',
        'accounting_allocation_id',
    ];

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function purchaseLines()
    {
        return $this->belongsTo(PurchaseLines::class, 'purchase_line_id');
    }

    public function purchaseReceiptLines()
    {
        return $this->belongsTo(PurchaseReceiptLines::class, 'purchase_receipt_line_id');
    }

    // Relation avec AccountingEntry pour l'entrée comptable liée à cette ligne de facture
    public function accountingEntry()
    {
        return $this->hasOne(AccountingEntry::class, 'invoice_line_id');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}