<?php

namespace App\Models\Products;

use App\Models\Planning\Task;
use App\Models\Products\Products;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockReservation extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_CONSUMED = 'consumed';
    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'task_id',
        'products_id',
        'qty_requested',
        'qty_reserved',
        'qty_missing',
        'status',
        'tracability',
    ];

    protected $casts = [
        'qty_requested' => 'float',
        'qty_reserved'  => 'float',
        'qty_missing'   => 'float',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'products_id');
    }
}
