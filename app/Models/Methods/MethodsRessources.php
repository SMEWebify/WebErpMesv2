<?php

namespace App\Models\Methods;

use App\Models\Planning\Task;
use App\Models\Methods\MethodsSection;
use App\Models\Methods\MethodsLocation;
use App\Models\Methods\MethodsServices;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsRessources extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable= ['ordre', 'code',  'label',  'picture', 'mask_time', 'capacity',  'section_id',  'color',  'methods_services_id',  'comment'];

    public function service()
    {
        return $this->belongsTo(MethodsServices::class, 'methods_services_id');
    }

    public function tasks() {
        return $this->belongsToMany(Task::class, 'task_resources')
                    ->withPivot(['autoselected_ressource', 'userforced_ressource'])
                    ->withTimestamps();
    }

    public function section()
    {
        return $this->belongsTo(MethodsSection::class, 'section_id');
    }

    public function locations()
    {
        return $this->hasMany(MethodsLocation::class, 'ressource_id');
    }

    /**
     * Calculate remaining available capacity for the given day.
     *
     * The capacity field represents the number of hours available per day
     * for this resource. This method sums the duration of tasks already
     * assigned on the provided date and subtracts this from the daily
     * capacity.
     */
    public function remainingCapacity(Carbon $date): float
    {
        $usedCapacity = $this->tasks()
            ->whereDate('start_date', $date->toDateString())
            ->get()
            ->sum(fn (Task $task) => $task->TotalTime());

        return max(0, $this->capacity - $usedCapacity);
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
        return date('d F Y', strtotime($this->created_at));
    }
}
