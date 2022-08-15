<?php

namespace App\Models\Products;

use App\Models\Products\StockLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stocks extends Model
{
    use HasFactory;

    protected $fillable = ['code',
                            'label', 
                            'user_id',];

    public function StockLocation()
    {
        return $this->hasMany(StockLocation::class);
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
