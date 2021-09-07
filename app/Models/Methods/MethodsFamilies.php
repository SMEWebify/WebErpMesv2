<?php

namespace App\Models\Methods;

use App\Models\Products\Products;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsFamilies extends Model
{
    use HasFactory;

    protected $fillable = ['CODE',  'LABEL',  'service_id'];

    public function service()
    {
        return $this->belongsTo(MethodsServices::class, 'service_id');
    }

    public function Product()
    {
        return $this->hasMany(Products::class);
    }


    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
