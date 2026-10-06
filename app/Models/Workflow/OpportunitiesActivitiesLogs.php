<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Workflow\Opportunities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpportunitiesActivitiesLogs extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable= [
        'opportunities_id',
        'user_id',
        'label',
        'type',
        'statu',
        'priority',
        'due_date',
        'comment',
    ];

    public function opportunity()
    {
        return $this->belongsTo(Opportunities::class, 'opportunities_id');
    }

    // Responsible user (falls back to the opportunity owner when null)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    /**
     * Get the formatted creation date of the line.
     *
     * This accessor method returns the creation date of line
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }
}
