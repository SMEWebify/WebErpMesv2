<?php

namespace App\Models\OSH;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OSHFormation extends Model
{
    use HasFactory;

    protected $table = 'osh_formations';

    protected $fillable = [
        'user_id',
        'type_of_training',
        'training_date',
        'expiration_date',
        'certification_obtained'
    ];

    // Relation avec l'utilisateur (user)
    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
