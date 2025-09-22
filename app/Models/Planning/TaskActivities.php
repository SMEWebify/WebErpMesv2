<?php

namespace App\Models\Planning;

use App\Models\User;
use App\Models\Planning\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskActivities extends Model
{
    use HasFactory;

    public const TYPE_START = 1;
    public const TYPE_END = 2;
    public const TYPE_FINISH = 3;
    public const TYPE_DECLARE_GOOD = 4;
    public const TYPE_DECLARE_BAD = 5;
    public const TYPE_COMMENT = 6;

    // Fillable attributes for mass assignment
    protected $fillable= ['task_id', 
                            'user_id',
                            'type',
                            'timestamp',
                            'good_qt',
                            'bad_qt',
                            'comment',];


    public function Tasks()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
        return date('d F Y - H:i:s', strtotime($this->created_at));
    }
}
