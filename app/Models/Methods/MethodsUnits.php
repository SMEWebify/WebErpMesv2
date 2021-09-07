<?php

namespace App\Models\Methods;

use App\Models\Products\Products;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsUnits extends Model
{
    use HasFactory;

    protected $fillable = ['CODE',  'LABEL',  'TYPE'];

    public function Product()
    {
        return $this->hasMany(Products::class);
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
