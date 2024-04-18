<?php

namespace App\Livewire;

use Livewire\Component;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class LogsViewer extends Component
{
    public $model;
    public $startDate;
    public $endDate;
    public $logs;
    public $availableModels;

    public function mount()
    {
        $this->startDate = Carbon::now()->toDateString();
        $this->endDate = Carbon::now()->toDateString();
        $this->availableModels = Activity::select('subject_type')->distinct()->pluck('subject_type');
    }

    public function filterLogs()
    {
        $this->validate([
            'model' => 'required|string',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);
    }

    public function render()
    {
        // Check if the form has been submitted
        if ($this->model || $this->startDate || $this->endDate) {
            $query = Activity::query();
    
            if ($this->model) {
                $query->where('subject_type', $this->model);
            }
    
            if ($this->startDate) {
                $query->whereDate('created_at', '>=', $this->startDate);
            }
    
            if ($this->endDate) {
                $query->whereDate('created_at', '<=', $this->endDate);
            }
    
            $this->logs = $query->latest()->get();
    
            // Convert JSON properties to PHP associative array for easier display
            $this->logs->transform(function ($log) {
                $log->properties = json_decode($log->properties, true);
                return $log;
            });
        } else {
            // If the form was not submitted, initialize $logs to null or an empty collection
            $this->logs = null; // or collect([]);
        }
    
        return view('livewire.logs-viewer');
    }
    
}
