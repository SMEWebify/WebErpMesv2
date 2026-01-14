<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_project_id',
        'type',
        'file_path',
        'file_name',
        'mime',
        'page_count',
        'version_label',
    ];

    public function Project()
    {
        return $this->belongsTo(InspectionProject::class, 'inspection_project_id');
    }
}
