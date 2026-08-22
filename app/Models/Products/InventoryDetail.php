<?php

namespace App\Models\Products;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryDetail extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_TO_CHECK  = 'to_check';

    protected $fillable = [
        'inventory_id',
        'stock_location_products_id',
        'products_id',
        'batch_id',
        'serial_number_id',
        'quality',
        'x_size',
        'y_size',
        'z_size',
        'nb_part',
        'surface_perc',
        'theoretical_qty',
        'reserved_qty',
        'unit_cost',
        'counted_qty',
        'counted_by',
        'counted_at',
        'status',
        'notes',
        'properties',
    ];

    protected $casts = [
        'theoretical_qty' => 'decimal:3',
        'reserved_qty'    => 'decimal:3',
        'unit_cost'       => 'decimal:4',
        'counted_qty'     => 'decimal:3',
        'x_size'          => 'decimal:3',
        'y_size'          => 'decimal:3',
        'z_size'          => 'decimal:3',
        'nb_part'         => 'integer',
        'surface_perc'    => 'decimal:3',
        'counted_at'      => 'datetime',
        'properties'      => 'array',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function stockLocationProduct(): BelongsTo
    {
        return $this->belongsTo(StockLocationProducts::class, 'stock_location_products_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'products_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(SerialNumbers::class, 'serial_number_id');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    /**
     * Difference between counted and theoretical, in stock units.
     * Positive when stock was found, negative when missing.
     */
    public function getVarianceAttribute(): ?float
    {
        if ($this->counted_qty === null) {
            return null;
        }

        return (float) $this->counted_qty - (float) $this->theoretical_qty;
    }

    /**
     * Monetary impact of the variance, for reporting only. The unit cost is
     * never rewritten by the inventory itself: CUMP/FIFO stays driven by
     * stock_moves valuation.
     */
    public function getVarianceValueAttribute(): ?float
    {
        $variance = $this->variance;

        return $variance === null ? null : round($variance * (float) $this->unit_cost, 2);
    }
}
