<?php

namespace App\Models\Methods;

use App\Models\Methods\MethodsFamilies;
use Illuminate\Database\Eloquent\Model;
use App\Models\Methods\MethodsRessources;
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

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

}
