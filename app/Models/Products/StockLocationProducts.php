<?php

namespace App\Models\Products;

use App\Models\User;
use App\Models\Products\Products;
use App\Models\Products\StockMove;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Purchases\PurchaseReceiptLines;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockLocationProducts extends Model
{
    use HasFactory;

    protected $fillable = ['code',
                            'user_id', 
                            'stock_locations_id',
                            'products_id', 
                            'mini_qty',
                            'end_date',
                            'addressing',
                        ];

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Product()
    {
        return $this->belongsTo(Products::class, 'products_id');
    }

    public function StockLocation()
    {
        return $this->belongsTo(StockLocation::class, 'stock_locations_id');
    }

    public function PurchaseReceiptLines()
    {
        return $this->hasMany(PurchaseReceiptLines::class);
    }

    public function StockMove()
    {
        return $this->hasMany(StockMove::class);
    }

    public function getTotalEntryStockMove()
    {
        return StockMove::where('stock_location_products_id', $this->id)
                        ->where(function (Builder $query) {
                            return $query->where('typ_move', '1')
                                        ->orwhere('typ_move', '3')
                                        ->orwhere('typ_move', '5')
                                        ->orwhere('typ_move', '12');
                        })
                        ->get()
                        ->sum('qty');
    }

    public function getTotalSortingStockMove()
    {
        return StockMove::where('stock_location_products_id', $this->id)
                        ->where(function (Builder $query) {
                            return $query->where('typ_move', '2')
                                        ->orwhere('typ_move', '6')
                                        ->orwhere('typ_move', '9');
                        })
                        ->get()
                        ->sum('qty');
    }

    public function getCurrentStockMove()
    {
        return $this->getTotalEntryStockMove() - $this->getTotalSortingStockMove();
    }

    public function GetPrettyCreatedAttribute()
    {
     return date('d F Y', strtotime($this->created_at));
    }
}
