<?php

namespace App\Models\Methods;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Methods\MethodsRessources;
use App\Models\Admin\UserEmploymentContracts;
use App\Models\OSH\OSHConformite;
use App\Models\OSH\OSHRisque;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsSection extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable= ['ordre','code', 'label', 'user_id','color'];

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Ressources()
    {
        return $this->hasMany(MethodsRessources::class, 'section_id');
    }

    public function Risque()
    {
        return $this->hasMany(OSHRisque::class);
    }

    public function Conformites()
    {
        return $this->hasMany(OSHConformite::class);
    }

    public function userEmploymentContracts()
    {
        return $this->hasMany(UserEmploymentContracts::class);
    }
}
