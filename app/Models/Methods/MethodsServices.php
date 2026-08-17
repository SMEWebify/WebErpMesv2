<?php

namespace App\Models\Methods;

use App\Models\Companies\Companies;
use App\Models\Products\Products;
use App\Models\Methods\MethodsFamilies;
use Illuminate\Database\Eloquent\Model;
use App\Models\Methods\MethodsRessources;
use App\Models\Planning\Task;
use App\Models\Quality\QualityControlDevice;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsServices extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nature du service (colonne `type`) : seul le type productif consomme de la
     * capacité interne. Matière, fournitures et sous-traitance ne doivent être
     * ni affectées à une ressource, ni comptées comme « tâches sans ressource ».
     */
    public const TYPE_PRODUCTIVE      = 1;
    public const TYPE_RAW_MATERIAL    = 2;
    public const TYPE_RAW_SHEET       = 3;
    public const TYPE_RAW_PROFILE     = 4;
    public const TYPE_RAW_BLOCK       = 5;
    public const TYPE_SUPPLIES        = 6;
    public const TYPE_SUB_CONTRACTING = 7;
    public const TYPE_COMPOSED        = 8;

    // Fillable attributes for mass assignment
    protected $fillable = ['code', 'ordre', 'label', 'type', 'hourly_rate', 'margin', 'color', 'picture', 'companies_id', 'is_nesting'];

    protected $casts = ['is_nesting' => 'boolean'];

    public function Families()
    {
        return $this->hasMany(MethodsFamilies::class);
    }

    /**
     * Ressources capables de réaliser ce service (pivot methods_ressource_service).
     * Une même machine peut apparaître sous plusieurs services : les calculs de
     * charge doivent donc dédoublonner par ressource, pas sommer par service.
     */
    public function Ressources()
    {
        return $this->belongsToMany(MethodsRessources::class, 'methods_ressource_service', 'methods_services_id', 'methods_ressources_id')
                    ->withPivot(['efficiency', 'hourly_rate', 'preference'])
                    ->withTimestamps()
                    ->orderBy('methods_ressource_service.preference');
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
