<?php

namespace App\Models\OSH;

use App\Models\HumanResources\TrainingType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OSHFormation extends Model
{
    use HasFactory;

    protected $table = 'osh_formations';

    // Fillable attributes for mass assignment
    protected $fillable= [
        'user_id',
        'training_type_id',
        'type_of_training',
        'training_date',
        'expiration_date',
        'certification_obtained'
    ];

    // Référentiel d'habilitation ; type_of_training reste la saisie libre héritée
    public function trainingType()
    {
        return $this->belongsTo(TrainingType::class, 'training_type_id');
    }

    // Relation avec l'utilisateur (user)
    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
