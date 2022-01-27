<?php

namespace App\Models\Products;

use App\Models\User;
use App\Models\Products\Products;
use Illuminate\Database\Eloquent\Model;
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

    public function GetPrettyCreatedAttribute()
    {
     return date('d F Y', strtotime($this->created_at));
    }
}
