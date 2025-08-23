<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'location',
        'characteristics',
        'contact_info',
    ];

    public function order()
    {
        return $this->belongsTo(Orders::class);
    }
}
