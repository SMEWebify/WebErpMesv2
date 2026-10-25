<?php

namespace App\Models\Inspection;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionMeasure extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_measure_session_id',
        'inspection_control_point_id',
        'serial_number',
        'part_index',
        'measured_value',
        'result',
        'deviation',
        'comment',
        'measured_by',
        'measured_at',
        'instrument_id',
        'quality_control_device_id',
        'quality_non_conformity_id',
    ];

    protected $casts = [
        'measured_at'    => 'datetime',
        'measured_value' => 'float',
        'deviation'      => 'float',
        'part_index'     => 'integer',
    ];

    public function Session()
    {
        return $this->belongsTo(InspectionMeasureSession::class, 'inspection_measure_session_id');
    }

    public function ControlPoint()
    {
        return $this->belongsTo(InspectionControlPoint::class, 'inspection_control_point_id');
    }

    /** Appareil du module qualité (avec ses étalonnages). */
    public function Device()
    {
        return $this->belongsTo(\App\Models\Quality\QualityControlDevice::class, 'quality_control_device_id');
    }

    public function NonConformity()
    {
        return $this->belongsTo(\App\Models\Quality\QualityNonConformity::class, 'quality_non_conformity_id');
    }

    public function MeasuredBy()
    {
        return $this->belongsTo(User::class, 'measured_by');
    }
}
