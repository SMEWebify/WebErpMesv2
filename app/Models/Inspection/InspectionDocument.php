<?php

namespace App\Models\Inspection;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

class InspectionDocument extends Model
{
    use HasFactory;

    const STATUS_DRAFT            = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED         = 'approved';
    const STATUS_OBSOLETE         = 'obsolete';

    protected $fillable = [
        'inspection_project_id',
        'type',
        'file_path',
        'file_name',
        'mime',
        'page_count',
        'version_label',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function Project()
    {
        return $this->belongsTo(InspectionProject::class, 'inspection_project_id');
    }

    public function Approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function submit(): void
    {
        $this->update(['status' => self::STATUS_PENDING_APPROVAL]);
    }

    public function approve(): void
    {
        $this->update([
            'status'      => self::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    public function makeObsolete(): void
    {
        $this->update(['status' => self::STATUS_OBSOLETE]);
    }
}
