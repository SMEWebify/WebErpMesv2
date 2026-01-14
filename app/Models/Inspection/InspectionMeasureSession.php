<?php

namespace App\Models\Inspection;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionMeasureSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_project_id',
        'session_code',
        'type',
        'quantity_to_measure',
        'started_at',
        'ended_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function Project()
    {
        return $this->belongsTo(InspectionProject::class, 'inspection_project_id');
    }

    public function Creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function Measures()
    {
        return $this->hasMany(InspectionMeasure::class);
    }
}
