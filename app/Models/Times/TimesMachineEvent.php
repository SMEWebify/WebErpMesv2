<?php

namespace App\Models\Times;

use Illuminate\Database\Eloquent\Model;
use App\Models\Times\TimesImproductTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimesMachineEvent extends Model
{
    use HasFactory;

    protected $fillable = ['CODE',  'ORDRE',  'LABEL',  'MASK_TIME',  'COLOR',  'ETAT'];

    public function improductTime()
    {
        return $this->hasMany(TimesImproductTime::class);
    }

}
