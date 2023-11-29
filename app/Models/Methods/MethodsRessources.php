<?php

namespace App\Models\Methods;

use App\Models\Planning\Task;
use App\Models\Methods\MethodsSection;
use App\Models\Methods\MethodsLocation;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsRessources extends Model
{
    use HasFactory;

    protected $fillable = ['ordre', 'code',  'label',  'picture', 'mask_time', 'capacity',  'section_id',  'color',  'methods_services_id',  'comment'];

    public function service()
    {
        return $this->belongsTo(MethodsServices::class, 'methods_services_id');
    }

    public function tasks() {
        return $this->belongsToMany(Task::class)
                    ->withPivot(['autoselected_ressource', 'userforced_ressource'])
                    ->withTimestamps();
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
