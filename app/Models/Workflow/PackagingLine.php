<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Model;

/**
 * Quantité d'une ligne de BL rangée dans un colis (liste de colisage).
 */
class PackagingLine extends Model
{
    protected $fillable = ['packaging_id', 'delivery_line_id', 'qty'];

    protected $casts = ['qty' => 'float'];

    public function packaging()
    {
        return $this->belongsTo(Packaging::class);
    }

    public function deliveryLine()
    {
        return $this->belongsTo(DeliveryLines::class, 'delivery_line_id');
    }
}
