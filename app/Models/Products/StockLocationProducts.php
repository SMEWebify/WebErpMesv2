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

    // Fillable attributes for mass assignment
    protected $fillable= ['code',
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

    public function getTotalEntryStockMove($traceability = null)
    {
        $query = StockMove::where('stock_location_products_id', $this->id)
                            ->where(function (Builder $query) {
                                return $query->where('typ_move', '1')
                                            ->orwhere('typ_move', '3')
                                            ->orwhere('typ_move', '5')
                                            ->orwhere('typ_move', '12');
                            });

        // Filtre par traçabilité si fourni
        if ($traceability) {
            $query->where('tracability', $traceability);
        }

        return $query->get()->sum('qty');
    }

    public function getTotalSortingStockMove($traceability = null)
    {
        $query = StockMove::where('stock_location_products_id', $this->id)
                            ->where(function (Builder $query) {
                                                                                return $query->where('typ_move', '2')
                                                                            ->orwhere('typ_move', '6')
                                                                            ->orwhere('typ_move', '9');
                                                            });

        // Filtre par traçabilité si fourni
        if ($traceability) {
        $query->where('tracability', $traceability);
        }

        return $query->get()->sum('qty');
    }

    public function getCurrentStockMove($traceability = null)
    {
        return $this->getTotalEntryStockMove($traceability) - $this->getTotalSortingStockMove($traceability);
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
        return date('d F Y', strtotime($this->created_at));
    }
}
