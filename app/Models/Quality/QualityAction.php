<?php

namespace App\Models\Quality;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityAction extends Model
{
    use HasFactory;

    protected $fillable = ['CODE',  'LABEL','TYPE', 'ETAT' ];

    public function User()
    {
        return $this->hasOne(User::class);
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
