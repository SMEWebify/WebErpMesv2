<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Workflow\CreditNotes;

class CreditNotesIndex extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'created_at'; // default sorting field
    public $sortAsc = false; // default sort direction
    public $searchIdStatus = '';

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc; 
        } else {
            $this->sortAsc = true; 
        }
        $this->sortField = $field;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function mount() 
    {

    }

    public function render()
    {
        $CreditNotes = CreditNotes::withCount('CreditNoteLines')
                                ->where('label','like', '%'.$this->search.'%')
                                ->where('statu', 'like', '%'.$this->searchIdStatus.'%')
                                ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                ->paginate(15);

        return view('livewire.credit-notes-index', [
            'CreditNotesList' => $CreditNotes,
        ]);
    }
}
