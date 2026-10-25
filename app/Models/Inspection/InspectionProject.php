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
        'fai_type',
        'report_fields',
        'report_comment',
        'verified_by',
        'verified_at',
        'approved_by',
        'approved_at',
        'customer_approval',
        'customer_approved_at',
        'report_file_id',
    ];

    protected $casts = [
        'is_fai'               => 'boolean',
        'serial_tracking'      => 'boolean',
        'closed_at'            => 'datetime',
        'report_fields'        => 'array',
        'verified_at'          => 'datetime',
        'approved_at'          => 'datetime',
        'customer_approved_at' => 'date',
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

    public function MeasureSessions()
    {
        return $this->hasMany(InspectionMeasureSession::class);
    }

    public function OrderLine()
    {
        return $this->belongsTo(\App\Models\Workflow\OrderLines::class, 'order_lines_id');
    }

    public function VerifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function ApprovedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function ReportFile()
    {
        return $this->belongsTo(\App\Models\File::class, 'report_file_id');
    }
}
