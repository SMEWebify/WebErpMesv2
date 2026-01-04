<?php

namespace App\Livewire;


use App\Jobs\CalculateTaskDates;
use App\Jobs\CalculateTaskResources;
use App\Services\TaskDateCalculator;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;


class TaskCalculationDate extends Component
{
    protected TaskDateCalculator $taskDateCalculator;

    public $Tasklists = [];
    public $progressDate = 0;
    public $progressRessource = 0;
    public $toBeCalculateDate = true;
    public $toBeCalculateRessource = true;

    public $progressDateMessages = [];
    public $countTaskCalculateDate = 0;
    public $progressRessourceMessages = [];
    public $countTaskCalculateRessource = 0;

    private const DATE_CACHE_KEY = CalculateTaskDates::CACHE_KEY;
    private const RESOURCE_CACHE_KEY = CalculateTaskResources::CACHE_KEY;
    
    public function boot(TaskDateCalculator $taskDateCalculator): void
    {
        $this->taskDateCalculator = $taskDateCalculator;
    }

    public function mount(): void
    {
        $this->updateProgress();
    }

    public function render()
    {
        return view('livewire.task-calculation-date', [
            'Tasklists' =>  $this->Tasklists,
            'countTaskCalculateDate' =>  $this->countTaskCalculateDate,
            'countTaskCalculateRessource' =>  $this->countTaskCalculateRessource,
            'progressDateMessages' =>  $this->progressDateMessages,
            'progressRessourceMessages' =>  $this->progressRessourceMessages,
        ]);
    }

    public function calculateRessource()
    {
        Cache::forget(self::RESOURCE_CACHE_KEY);
        CalculateTaskResources::dispatchAfterResponse();
        $this->toBeCalculateRessource = false;
        $this->updateProgress();
    }

    public function calculateDate()
    {
        Cache::forget(self::DATE_CACHE_KEY);
        CalculateTaskDates::dispatchAfterResponse();
        $this->toBeCalculateDate = false;
        $this->updateProgress();
    }


    public function updateProgress(): void
    {
        $dateState = Cache::get(self::DATE_CACHE_KEY, []);
        $resourceState = Cache::get(self::RESOURCE_CACHE_KEY, []);

        $this->progressDate = $dateState['progress'] ?? 0;
        $this->countTaskCalculateDate = $dateState['count'] ?? 0;
        $this->progressDateMessages = $dateState['messages'] ?? [];

        $this->progressRessource = $resourceState['progress'] ?? 0;
        $this->countTaskCalculateRessource = $resourceState['count'] ?? 0;
        $this->progressRessourceMessages = $resourceState['messages'] ?? [];

        if (($dateState['status'] ?? null) === 'running') {
            $this->toBeCalculateDate = false;
        }

        if (($resourceState['status'] ?? null) === 'running') {
            $this->toBeCalculateRessource = false;
        }
    }
    
}
