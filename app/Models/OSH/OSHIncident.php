<?php

namespace App\Models\OSH;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OSHIncident extends Model
{
    use HasFactory;

    protected $table = 'osh_incidents';

    protected $fillable = [
        'incident_date',
        'description',
        'user_id',
        'severity',
        'corrective_actions',
        'statut',
        'resolution_date'
    ];

    // Relation avec l'utilisateur (user)
    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
