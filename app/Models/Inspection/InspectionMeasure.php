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
        'measured_value',
        'result',
        'deviation',
        'comment',
        'measured_by',
        'measured_at',
        'instrument_id',
    ];

    protected $casts = [
        'measured_at' => 'datetime',
    ];

    public function Session()
    {
        return $this->belongsTo(InspectionMeasureSession::class, 'inspection_measure_session_id');
    }

    public function ControlPoint()
    {
        return $this->belongsTo(InspectionControlPoint::class, 'inspection_control_point_id');
    }

    public function Instrument()
    {
        return $this->belongsTo(InspectionInstrument::class, 'instrument_id');
    }

    public function MeasuredBy()
    {
        return $this->belongsTo(User::class, 'measured_by');
    }
}
