<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Chat;
use Illuminate\Support\Facades\Auth;

class ChatLive extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'id'; // default sorting field
    public $sortAsc = true; // default sort direction
    public $label;
    public $idItem = '';
    public $Class = '';

    private $ChatMessages;

    // Validation Rules
    protected $rules = [
        'label'=>'required',
    ];


    public function render()
    {
        
        $ChatMessages = $this->ChatMessages = Chat::where('related_id', $this->idItem)
                                                    ->where('related_type', $this->Class)
                                                    ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                                    ->get();

        return view('livewire.chatlive', [
            'ChatMessages' => $ChatMessages,
            'idItem' => $this->idItem,
            'Class' => $this->Class,

        ]);
    }

    public function storeMessage($id ,$Class){
        $this->validate();

        // Create Line
        $Todo = Chat::create([
            'label'=> $this->label,  
            'user_id'=> Auth::id(), 
            'related_id' => $id,
            'related_type' => $Class,
        ]);
    }
    
}