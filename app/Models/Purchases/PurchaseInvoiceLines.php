<?php

namespace App\Models\Purchases;

use App\Models\Purchases\PurchaseLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Purchases\PurchaseInvoice;
use App\Models\Purchases\PurchaseReceiptLines;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseInvoiceLines extends Model
{
    protected $fillable = [
        
        'purchase_invoice_id',
        'purchase_receipt_line_id',
        'purchase_line_id',
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

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}