<?php

namespace App\Livewire;

use App\Models\Mood;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class MoodTracker extends Component
{
    public $mood;
    public $date;
    public $teamMoods;

    public function mount()
    {
        $this->date = date('Y-m-d');
        $this->loadCurrentMood();
        $this->loadTeamMoods();
    }

    public function selectMood($selectedMood)
    {
        $this->mood = $selectedMood;

        Mood::updateOrCreate(
            ['user_id' => Auth::id(), 'date' => $this->date],
            ['mood' => $this->mood]
        );

        $this->loadTeamMoods();
    }

    public function loadCurrentMood()
    {
        $currentMood = Mood::where('user_id', Auth::id())->where('date', $this->date)->first();
        $this->mood = $currentMood ? $currentMood->mood : null;
    }


    public function loadTeamMoods()
    {
        $this->teamMoods = Mood::where('date', $this->date)->get();
    }

    public function render()
    {
        return view('livewire.mood-tracker');
    }
}
