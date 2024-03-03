<?php

namespace App\Models\Planning;

use App\Models\User;
use App\Models\Planning\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskActivities extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 
                            'user_id',
                            'type',
                            'timestamp',
                            'good_qt',
                            'bad_qt',
                            'comment',];


    public function Taks()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y - H:i:s', strtotime($this->created_at));
    }
}
