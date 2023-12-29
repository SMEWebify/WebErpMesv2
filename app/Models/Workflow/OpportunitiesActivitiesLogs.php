<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpportunitiesActivitiesLogs extends Model
{
    use HasFactory;

    protected $fillable = [
        'opportunites_id',
        'label',
        'type',
        'statu',
        'priority',
        'due_date',
        'comment',
    ];

    public function opportunity()
    {
        return $this->belongsTo(User::class, 'opportunites_id');
    }
}
