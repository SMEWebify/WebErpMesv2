<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Products\Products;

class StockCurrent extends Component
{

    public $produitsAvecStock = [];
    public $showProductsWithStock = false;

    public function mount()
    {
        
    }

    public function render()
    {

        return view('livewire.stock-current');
    }

    public function showStock()
    {
        // Définissez $showStock sur vrai pour afficher la liste des stocks
        $this->produitsAvecStock = Products::with('Stock_location_product')->get();
    }

    
}
