<?php

namespace App\Models\Quality;

use App\Models\User;
use App\Models\Products\Products;
use Illuminate\Database\Eloquent\Model;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QualityAmdec extends Model
{
    use HasFactory;

    protected $fillable = [
                        'product_id',
                        'user_id',
                        'failure_mode',
                        'effect',
                        'cause',
                        'severity',
                        'occurrence',
                        'detection',
                        'rpn',
                        'current_control',
                        'recommended_action'
                    ];


    public function actions()
    {
        return $this->hasMany(QualityAction::class, 'amdec_id');
    }

    public function derogations()
    {
        return $this->hasMany(QualityDerogation::class, 'amdec_id');
    }

    public function nonConformities()
    {
        return $this->hasMany(QualityNonConformity::class, 'amdec_id');
    }

    public function controlTools()
    {
        return $this->hasMany(QualityControlDevice::class, 'amdec_id');
    }

    public function calculateRPN()
    {
        return $this->severity * $this->occurrence * $this->detection;
    }

    public function calculateCriticality()
    {
        return $this->severity * $this->occurrence;
    }

    public function categorizeCriticality()
    {
        $criticality = $this->calculateCriticality();

        if ($criticality >= 8) {
            return 'High'; // Red
        } elseif ($criticality >= 4 && $criticality < 8) {
            return 'Medium'; // Orange
        } else {
            return 'Low'; // Green
        }
    }

    public function Product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
