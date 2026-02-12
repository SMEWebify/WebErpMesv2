<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class PreOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 1;
    public const STATUS_CONVERTED = 2;

    protected $fillable = [
        'pre_order_import_id',
        'source_pdf',
        'status',
        'converted_order_id',
        'converted_at',
        'comment',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
    ];

    public function importBatch()
    {
        return $this->belongsTo(PreOrderImport::class, 'pre_order_import_id');
    }

    public function lines()
    {
        return $this->hasMany(PreOrderLine::class);
    }

    public function convertedOrder()
    {
        return $this->belongsTo(Orders::class, 'converted_order_id');
    }

    public function getComputedTotalPriceAttribute(): float
    {
        return $this->lines->sum(function (PreOrderLine $line) {
            return $line->effective_total_price;
        });
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';

        return Number::currency($this->computed_total_price, $currency, config('app.locale'));
    }

}

