<?php

namespace App\Models\Methods;

use App\Models\Companies\Companies;
use App\Models\Products\Products;
use App\Models\Methods\MethodsFamilies;
use Illuminate\Database\Eloquent\Model;
use App\Models\Methods\MethodsRessources;
use App\Models\Planning\Task;
use App\Models\Quality\QualityControlDevice;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsServices extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable= ['code','ordre', 'label','type', 'hourly_rate','margin', 'color','picture', 'companies_id'];

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

    public function Tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function Suppliers()
    {
        return $this->belongsToMany(Companies::class, 'methods_service_suppliers', 'methods_service_id', 'companies_id')
            ->withTimestamps();
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
