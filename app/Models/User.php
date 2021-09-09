<?php

namespace App\Models;

use App\Models\Companies\Companies;
use App\Models\Quality\QualityAction;
use App\Models\Methods\MethodsSection;
use App\Models\Products\StockLocation;
use Illuminate\Notifications\Notifiable;
use App\Models\Quality\QualityDerogation;
use App\Models\Quality\QualityControlDevice;
use App\Models\Quality\QualityNonConformity;
use App\Models\Products\StockLocationProducts;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    public function companie()
    {
        return $this->hasMany(Companies::class);
    }

    public function section()
    {
        return $this->hasMany(MethodsSection::class);
    }

    public function quality_actions()
    {
        return $this->hasMany(QualityAction::class);
    }

    public function quality_control_device()
    {
        return $this->hasMany(QualityControlDevice::class);
    }

    public function quality_derogations()
    {
        return $this->hasMany(QualityDerogation::class);
    }

    public function quality_non_conformities()
    {
        return $this->hasMany(QualityNonConformity::class);
    }

    public function absence_request()
    {
        return $this->hasMany(TimesAbsence::class);
    }

    public function stock_location()
    {
        return $this->hasMany(StockLocation::class);
    }

    public function stock_location_product()
    {
        return $this->hasMany(StockLocationProducts::class);
    }

    
}
