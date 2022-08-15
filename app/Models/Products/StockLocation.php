<?php

namespace App\Models\Products;

use App\Models\User;
use App\Models\Products\Stocks;
use Illuminate\Database\Eloquent\Model;
use App\Models\Products\StockLocationProducts;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockLocation extends Model
{
    use HasFactory;

    protected $fillable = ['code',
                        'label', 
                        'stocks_id',
                        'user_id',
                        'end_date',
                        'comment',];

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Stocks()
    {
        return $this->belongsTo(Stocks::class, 'stocks_id');
    }

    public function StockLocationProducts()
    {
        return $this->hasMany(StockLocationProducts::class, 'stock_locations_id');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
