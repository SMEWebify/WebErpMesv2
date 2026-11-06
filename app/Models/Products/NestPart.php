<?php

namespace App\Models\Products;

use App\Models\Planning\Task;
use App\Models\Workflow\OrderLines;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pièce d'une imbrication : quantité découpée pour une ligne de commande. */
class NestPart extends Model
{
    protected $fillable = [
        'nest_id', 'order_line_id', 'task_id', 'part_ref', 'external_ref',
        'qty', 'good_qty', 'bad_qty', 'area_mm2', 'weight_kg',
    ];

    protected $casts = [
        'qty'       => 'float',
        'good_qty'  => 'float',
        'bad_qty'   => 'float',
        'area_mm2'  => 'float',
        'weight_kg' => 'float',
    ];

    public function nest(): BelongsTo
    {
        return $this->belongsTo(Nest::class);
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLines::class, 'order_line_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
