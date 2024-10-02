<?php

namespace App\Models\OSH;

use App\Models\User;
use App\Models\Methods\MethodsSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OSHRisque extends Model
{
    use HasFactory;

    protected $table = 'osh_risques';

    protected $fillable = [
        'section_id',
        'description',
        'severity',
        'probability',
        'preventive_measures',
        'user_id'
    ];

    // Relation avec la section

    public function section()
    {
        return $this->belongsTo(MethodsSection::class);
    }

    // Relation avec l'utilisateur (user)
    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
