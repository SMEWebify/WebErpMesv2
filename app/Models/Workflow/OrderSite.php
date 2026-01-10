<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'label',
        'location',
        'characteristics',
        'contact_info',
    ];

    public function Order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }

    public function OrderSiteImplantations()
    {
        return $this->hasMany(OrderSiteImplantation::class);
    }
  
    public function implantations()
    {
        return $this->hasMany(OrderSiteImplantation::class);

    }
}
