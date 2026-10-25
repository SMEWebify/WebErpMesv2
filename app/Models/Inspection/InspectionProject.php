<?php

namespace App\Models\Inspection;

use App\Models\User;
use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'companies_id',
        'orders_id',
        'order_lines_id',
        'of_id',
        'inspection_plan_id',
        'products_id',
        'revision',
        'is_fai',
        'status',
        'quantity_planned',
        'serial_tracking',
        'created_by',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'is_fai'          => 'boolean',
        'serial_tracking' => 'boolean',
        'closed_at'       => 'datetime',
    ];

    public function Plan()
    {
        return $this->belongsTo(InspectionPlan::class, 'inspection_plan_id');
    }

    public function Order()
    {
        return $this->belongsTo(\App\Models\Workflow\Orders::class, 'orders_id');
    }

    public function Measures()
    {
        return $this->hasManyThrough(InspectionMeasure::class, InspectionMeasureSession::class,
            'inspection_project_id', 'inspection_measure_session_id');
    }

    public function Company()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

    public function Creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function Documents()
    {
        return $this->hasMany(InspectionDocument::class);
    }

    public function ControlPoints()
    {
        return $this->hasMany(InspectionControlPoint::class)->orderBy('order');
    }

    public function MeasureSessions()
    {
        return $this->hasMany(InspectionMeasureSession::class);
    }

    public function NonConformities()
    {
        return $this->hasMany(InspectionNonconformity::class);
    }
}
