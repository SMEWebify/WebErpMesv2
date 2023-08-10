<?php

namespace App\Models\Methods;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Methods\MethodsRessources;
use App\Models\Admin\UserEmploymentContracts;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsSection extends Model
{
    use HasFactory;

    protected $fillable = ['ordre','code', 'label', 'user_id','color'];

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Ressources()
    {
        return $this->hasMany(MethodsRessources::class);
    }

    public function userEmploymentContracts()
    {
        return $this->hasMany(UserEmploymentContracts::class);
    }
}
