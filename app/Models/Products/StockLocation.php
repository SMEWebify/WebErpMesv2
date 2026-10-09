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

    // Fillable attributes for mass assignment
    protected $fillable= ['code',
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

    /**
     * Get the formatted creation date of the line.
     *
     * This accessor method returns the creation date of line
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return \Illuminate\Support\Carbon::parse($this->created_at)->locale(app()->getLocale())->translatedFormat('d F Y');
    }
}
