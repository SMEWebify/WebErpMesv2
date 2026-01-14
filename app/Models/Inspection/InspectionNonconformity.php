<?php

namespace App\Models\Inspection;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionNonconformity extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_project_id',
        'inspection_measure_id',
        'title',
        'description',
        'status',
        'created_by',
    ];

    public function Project()
    {
        return $this->belongsTo(InspectionProject::class, 'inspection_project_id');
    }

    public function Measure()
    {
        return $this->belongsTo(InspectionMeasure::class, 'inspection_measure_id');
    }

    public function Creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
