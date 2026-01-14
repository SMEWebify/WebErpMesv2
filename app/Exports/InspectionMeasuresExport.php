<?php

namespace App\Exports;

use App\Models\Inspection\InspectionMeasure;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class InspectionMeasuresExport implements FromCollection, WithHeadings, WithMapping
{
    private $inspectionProjectId;

    public function __construct($inspectionProjectId)
    {
        $this->inspectionProjectId = $inspectionProjectId;
    }

    public function headings(): array
    {
        return [
            'SESSION_CODE',
            'SESSION_TYPE',
            'POINT_NUMBER',
            'POINT_LABEL',
            'SERIAL_NUMBER',
            'MEASURED_VALUE',
            'RESULT',
            'DEVIATION',
            'MEASURED_BY',
            'MEASURED_AT',
            'INSTRUMENT',
            'COMMENT',
        ];
    }

    public function map($measure): array
    {
        return [
            $measure->Session?->session_code,
            $measure->Session?->type,
            $measure->ControlPoint?->number,
            $measure->ControlPoint?->label,
            $measure->serial_number,
            $measure->measured_value,
            $measure->result,
            $measure->deviation,
            $measure->MeasuredBy?->name,
            optional($measure->measured_at)->format('Y-m-d H:i:s'),
            $measure->Instrument?->name,
            $measure->comment,
        ];
    }

    public function collection()
    {
        return InspectionMeasure::with(['Session', 'ControlPoint', 'MeasuredBy', 'Instrument'])
            ->whereHas('Session', function ($query) {
                $query->where('inspection_project_id', $this->inspectionProjectId);
            })
            ->orderBy('measured_at')
            ->get();
    }
}
