<?php

namespace App\Models\Purchases;

use App\Models\Products\Batch;
use App\Models\Products\StockMove;
use App\Models\Purchases\PurchaseLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Quality\QualityNonConformity;
use App\Models\Products\StockLocationProducts;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PurchaseReceiptLines extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * Seules les modifications sont journalisées : la création d'une ligne est déjà
     * tracée par la réception, et une ligne neuve ne porte pas encore de visa.
     */
    protected static $recordEvents = ['updated'];

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
        'received_certificate_type',
        // document_check_* : hors $fillable, écrits seulement par ReceiptDocumentCheck
    ];

    /**
     * Cast attributes to common representations.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'inspection_date' => 'date',
        'document_check_at' => 'datetime',
    ];

    /**
     * Journal du visa documentaire (ISO 19443) : certificat reçu, visa, NC liée.
     * Les autres champs de la ligne (quantités, inspection physique) ne sont pas tracés ici.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['received_certificate_type', 'document_check_by', 'document_check_at',
                'document_check_result', 'document_check_comment', 'quality_non_conformity_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

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

    // Lots (coulées) créés à la mise en stock de la ligne
    public function batches()
    {
        return $this->hasMany(Batch::class, 'purchase_receipt_line_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function documentChecker()
    {
        return $this->belongsTo(User::class, 'document_check_by');
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
        return \Illuminate\Support\Carbon::parse($this->created_at)->locale(app()->getLocale())->translatedFormat('d F Y');
    }
}
