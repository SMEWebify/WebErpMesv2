<?php

namespace App\Models\Planning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskResources extends Model
{
    use HasFactory;

    /** Nature de la capacité consommée par l'affectation. */
    public const ROLE_MACHINE = 'machine';
    public const ROLE_LABOR   = 'labor';

    /** Origine de l'affectation. */
    public const SOURCE_AUTO   = 'auto';   // ordonnancement automatique
    public const SOURCE_MANUAL = 'manual'; // choix utilisateur à la création/modification de la tâche
    public const SOURCE_FORCED = 'forced'; // forçage explicite depuis le planning

    public const ROLES   = [self::ROLE_MACHINE, self::ROLE_LABOR];
    public const SOURCES = [self::SOURCE_AUTO, self::SOURCE_MANUAL, self::SOURCE_FORCED];

    protected $table = 'task_resources';

    // Fillable attributes for mass assignment
    protected $fillable= ['task_id',
                            'methods_ressources_id',
                            'role',
                            'source',
                            'load_factor',];
}
