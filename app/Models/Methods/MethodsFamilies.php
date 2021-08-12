<?php

namespace App\Models\Methods;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MethodsFamilies extends Model
{
    use HasFactory;

    protected $fillable = ['CODE',  'LABEL',  'service_id'];

    public function service()
    {
        return $this->belongsTo(MethodsServices::class, 'service_id');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
