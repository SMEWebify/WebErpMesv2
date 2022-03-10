<?php

namespace App\View\Components;

use Illuminate\View\Component;

class EmptyDataLine extends Component
{
    public $col;
    public $text;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct( $col, $text)
    {
        $this->col = $col;
        $this->text = $text;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.empty-data-line');
    }
}
