<?php

namespace App\Models\Inspection;

use App\Models\File;
use App\Models\User;
use App\Models\Products\Products;
use Illuminate\Database\Eloquent\Model;

/**
 * Plan de contrôle d'un article pour une révision : le plan de la GED et les
 * cotes bullées dessus. Brouillon tant qu'il n'est pas validé, figé ensuite.
 */
class InspectionPlan extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_OBSOLETE = 'obsolete';

    protected $fillable = [
        'products_id',
        'revision',
        'file_id',
        'page_count',
        'rotation',
        'status',
        'thumbnail_path',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'page_count'  => 'integer',
        'rotation'    => 'integer',
    ];

    /**
     * Point du repère de la page (proportions 0–1) dans le repère affiché, une
     * fois le plan tourné de `rotation` degrés dans le sens horaire.
     *
     * @return array{0: float, 1: float}
     */
    public function toView(float $x, float $y): array
    {
        return match ($this->rotation % 360) {
            90      => [1 - $y, $x],
            180     => [1 - $x, 1 - $y],
            270     => [$y, 1 - $x],
            default => [$x, $y],
        };
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'products_id');
    }

    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    public function points()
    {
        return $this->hasMany(InspectionControlPoint::class, 'inspection_plan_id')->orderBy('number');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
