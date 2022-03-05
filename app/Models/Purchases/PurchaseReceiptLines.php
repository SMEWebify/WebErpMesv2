<?php

namespace App\Models\Purchases;

use App\Models\Purchases\PurchaseLines;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseReceiptLines extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_receipt_id',
        'purchase_line_id',
        'ordre',
        'receipt_qty',

    ];

    public function purchaseLines()
    {
        return $this->belongsTo(PurchaseLines::class, 'purchase_line_id');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
