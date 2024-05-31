<?php

namespace App\Models\Companies;

use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierRating extends Model
{
    use HasFactory;

    protected $fillable = ['purchases_id', 'companies_id', 'rating', 'comment'];

    public function purchaseOrder()
    {
        return $this->belongsTo(Purchases::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Companies::class);
    }
}
