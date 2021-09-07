<?php

namespace App\Models\Methods;

use App\Models\Products\Products;
use App\Models\Methods\MethodsFamilies;
use Illuminate\Database\Eloquent\Model;
use App\Models\Methods\MethodsRessources;
use App\Models\Quality\QualityControlDevice;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsServices extends Model
{
    use HasFactory;

    protected $fillable = ['CODE','ORDRE', 'LABEL','TYPE', 'HOURLY_RATE','MARGIN', 'COLOR','PICTURE', 'compannie_id'];

    public function Families()
    {
        return $this->hasMany(MethodsFamilies::class);
    }

    public function Ressources()
    {
        return $this->hasMany(MethodsRessources::class);
    }

    public function quality_control_device()
    {
        return $this->hasMany(QualityControlDevice::class);
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
