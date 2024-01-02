<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpportunitiesActivitiesLogs extends Model
{
    use HasFactory;

    protected $fillable = [
        'opportunities_id',
        'label',
        'type',
        'statu',
        'priority',
        'due_date',
        'comment',
    ];

    public function opportunity()
    {
        return $this->belongsTo(User::class, 'opportunities_id');
    }

    
    //Get Created attribute like '	06 December 2023'
    public function GetPrettyCreatedAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }
}
