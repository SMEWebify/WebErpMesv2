<?php

namespace App\Models\Workflow;

use App\Models\Workflow\Orders;
use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderRating extends Model
{
    use HasFactory;

    protected $fillable = ['orders_id', 'companies_id', 'rating', 'comment'];

    public function order()
    {
        return $this->belongsTo(Orders::class);
    }

    // Relationship with the company associated with the order
    public function companie()
    {
        return $this->belongsTo(Companies::class);
    }
}
