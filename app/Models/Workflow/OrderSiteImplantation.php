<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderSiteImplantation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_site_id',
        'name',
        'description',
      'workforce',
        'equipment',
        'step',
        'start_date',
        'end_date',
        'notes',
    ];

    public function OrderSite()
    {
        return $this->belongsTo(OrderSite::class, 'order_site_id');
    }



    public function orderSite()
    {
        return $this->belongsTo(OrderSite::class);
    }
}
