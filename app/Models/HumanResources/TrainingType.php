<?php

namespace App\Models\HumanResources;

use App\Models\Methods\MethodsRessources;
use App\Models\OSH\OSHFormation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A training or authorisation an employee can hold (CACES, pontier, habilitation
 * électrique, soudure...).
 *
 * Linking it to resources is what turns the training register into a
 * versatility matrix: it answers "who may run the press brake?". The link is
 * informative only — nothing in the planning or the task assignment reads it to
 * refuse an operation.
 */
class TrainingType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'color',
        'validity_months',
        'ordre',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'validity_months' => 'integer',
    ];

    public function trainings()
    {
        return $this->hasMany(OSHFormation::class, 'training_type_id');
    }

    /**
     * Resources this authorisation is expected for.
     */
    public function resources()
    {
        return $this->belongsToMany(
            MethodsRessources::class,
            'training_type_resource',
            'training_type_id',
            'methods_ressources_id'
        )->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
