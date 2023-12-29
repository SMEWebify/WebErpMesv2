<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpportunitiesEventsLogs extends Model
{
    use HasFactory;

    protected $fillable = [
        'opportunites_id',
        'label',
        'type',
        'start_date',
        'end_date',
        'comment',
    ];

    public function opportunity()
    {
        return $this->belongsTo(User::class, 'opportunites_id');
    }
}
