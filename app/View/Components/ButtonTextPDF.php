<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ButtonTextPDF extends Component
{
    public $route;
    
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct( $route)
    {
        $this->route = $route;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.button-text-p-d-f');
    }
}
