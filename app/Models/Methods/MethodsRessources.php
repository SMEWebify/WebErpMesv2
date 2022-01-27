<?php

namespace App\Models\Methods;

use App\Models\Methods\MethodsSection;
use App\Models\Methods\MethodsLocation;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsRessources extends Model
{
    use HasFactory;

    protected $fillable = ['ORDRE', 'code',  'label',  'picture', 'mask_time', 'capacity',  'section_id',  'color',  'service_id',  'comment'];

    public function service()
    {
        return $this->belongsTo(MethodsServices::class, 'service_id');
    }

    public function section()
    {
        return $this->belongsTo(MethodsSection::class, 'section_id');
    }

    public function locations()
    {
        return $this->hasMany(MethodsLocation::class);
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
