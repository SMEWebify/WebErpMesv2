<?php

namespace App\Livewire;

use Livewire\Component;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;
use App\Models\User;

class LogsViewer extends Component
{
    public $model;
    public $startDate;
    public $endDate;
    public $logs;
    public $availableModels;
    public $subjectType;  // Variable for the model type (quote, order, etc.)
    public $subjectId;    // Variable for the model ID
    public $userId;
    public $availableUsers;

    public function mount($subjectType = null, $subjectId = null)  // Pass the model type and ID
    {
        $this->startDate = Carbon::now()->toDateString();
        $this->endDate = Carbon::now()->toDateString();
        $this->availableModels = Activity::select('subject_type')->distinct()->pluck('subject_type');
        $this->availableUsers = User::select('id', 'name')->get();
        $this->subjectType = $subjectType;  // Initialize the model type
        $this->subjectId = $subjectId;      // Initialize the model ID
    }

    public function filterLogs()
    {
        $rules = [
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'userId' => 'nullable|integer',
        ];

        if (!$this->subjectType) {
            $rules['model'] = 'required|string';
        }

        $this->validate($rules);
    }

    public function render()
    {
        // If filters are submitted or a specific topic is set
        if ($this->model || $this->startDate || $this->endDate || $this->subjectType || $this->subjectId || $this->userId) {
            $query = Activity::query();

            if ($this->model) {
                $query->where('subject_type', $this->model);
            }

            if ($this->subjectType && $this->subjectId) {
                // Filter by model type and ID (e.g., Quote, Order, etc.)
                $query->where('subject_type', $this->subjectType)
                    ->where('subject_id', $this->subjectId);
            }

            if ($this->startDate) {
                $query->whereDate('created_at', '>=', $this->startDate);
            }

            if ($this->endDate) {
                $query->whereDate('created_at', '<=', $this->endDate);
            }

            if ($this->userId) {
                $query->where('causer_id', $this->userId);
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
