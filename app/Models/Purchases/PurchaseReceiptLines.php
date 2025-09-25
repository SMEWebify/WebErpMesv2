<?php

namespace App\Models\Purchases;

use App\Models\Products\StockMove;
use App\Models\Purchases\PurchaseLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Quality\QualityNonConformity;
use App\Models\Products\StockLocationProducts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class PurchaseReceiptLines extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable= [
        'purchase_receipt_id',
        'purchase_line_id',
        'ordre',
        'receipt_qty',
        'stock_location_products_id',
        'inspected_by',
        'inspection_date',
        'accepted_qty',
        'rejected_qty',
        'inspection_result',
        'quality_non_conformity_id',
    ];

    /**
     * Cast attributes to common representations.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'inspection_date' => 'date',
    ];

    public function purchaseReceipt()
    {
        return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id');
    }

    public function purchaseLines()
    {
        return $this->belongsTo(PurchaseLines::class, 'purchase_line_id');
    }

    public function StockLocationProducts()
    {
        return $this->belongsTo(StockLocationProducts::class, 'stock_location_products_id');
    }

    public function StockMove()
    {
        return $this->hasMany(StockMove::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function qualityNonConformity()
    {
        return $this->belongsTo(QualityNonConformity::class, 'quality_non_conformity_id');
    }

    /**
     * Get the formatted creation date of the line.
     *
     * This accessor method returns the creation date of line
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
