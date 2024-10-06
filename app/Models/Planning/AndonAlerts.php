<?php

namespace App\Models\Planning;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Planning\Task;
use Illuminate\Database\Eloquent\Model;
use App\Models\Methods\MethodsRessources;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AndonAlerts extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'methods_ressources_id',
        'type',
        'description',
        'status',
        'triggered_at',
        'resolved_at',
        'user_id'
    ];

    /**
    * Alert related to a task.
    */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
    * Alert related to a resource (equipment, machine, etc.).
    */
    public function resource()
    {
        return $this->belongsTo(MethodsRessources::class);
    }

    /**
    * Person responsible for resolving the alert.
    */
    public function responsiblePerson()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mark the alert as in progress.
     */
    public function markinProgressAlert($userId)
    {
        $this->update([
            'status' => 2,
            'user_id' => $userId,
        ]);
    }

    /**
     * Mark the alert as resolved.
     */
    public function markAsResolved($userId)
    {
        $this->update([
            'status' => 3,
            'resolved_at' => now(),
            'user_id' => $userId,
        ]);
    }

    /**
     * Check if the alert is resolved.
     */
    public function isResolved()
    {
        return $this->status === 3;
    }

    
    // Relationship with the user associated with the order
    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function GetPrettyResolveddAttribute()
    {
        return Carbon::parse($this->resolved_at)->diffForHumans();
    }

    public function GetPrettyCreatedAttribute()
    {
        return Carbon::parse($this->triggered_at)->diffForHumans();
    }

    public function getTimeToResolveAttribute()
{
    if ($this->resolved_at) {
        $triggeredAt = Carbon::parse($this->triggered_at);
        $resolvedAt = Carbon::parse($this->resolved_at);

        return $triggeredAt->diffForHumans($resolvedAt, true); // Returns a human-readable difference
    }

    return null; // or return 'Not resolved' if you prefer
}
}
