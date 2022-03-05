<?php

namespace App\View\Components;

use Illuminate\View\Component;

class CompanieButton extends Component
{
    public $id;
    public $label;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct( $id, $label)
    {
        $this->id = $id;
        $this->label = $label;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.companie-button');
    }
}
