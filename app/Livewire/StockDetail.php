<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Products\StockMove;
use Illuminate\Support\Facades\Auth;

class StockDetail extends Component
{
    public $search = '';
    public $user_id ;
    public $StockDetails ;

    public function mount($id) 
    {
        $this->user_id = Auth::id();
        $this->search = $id;
        $this->StockDetails = StockMove::find($this->search);
    }

    public function render()
    {
        if(!empty($this->search)){
            $this->StockDetails = StockMove::find($this->search);
        }
        
        return view('livewire.stock-detail', [
            'StockDetails' => $this->StockDetails,
        ]);
    }
}
