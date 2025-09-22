<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Quality\QualityNonConformity;

class Returns extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'statu',
        'deliverys_id',
        'quality_non_conformity_id',
        'created_by',
        'diagnosed_by',
        'customer_report',
        'diagnosis',
        'resolution_notes',
        'received_at',
        'diagnosed_at',
        'closed_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'diagnosed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected $appends = ['pretty_created', 'status_label'];

    public function delivery()
    {
        return $this->belongsTo(Deliverys::class, 'deliverys_id');
    }

    public function qualityNonConformity()
    {
        return $this->belongsTo(QualityNonConformity::class, 'quality_non_conformity_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function diagnoser()
    {
        return $this->belongsTo(User::class, 'diagnosed_by');
    }

    public function lines()
    {
        return $this->hasMany(ReturnLines::class, 'return_id');
    }

    public function GetPrettyCreatedAttribute(): string
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->statu) {
            1 => __('returns.status.received'),
            2 => __('returns.status.diagnosed'),
            3 => __('returns.status.in_rework'),
            4 => __('returns.status.closed'),
            default => __('general_content.undefined_trans_key'),
        };
    }
}
