<?php

namespace App\Models\Methods;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MethodsUnits extends Model
{
    use HasFactory;

    protected $fillable = ['CODE',  'LABEL',  'TYPE'];

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
