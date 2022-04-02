<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ContentHeaderPreviousButton extends Component
{
    public $h1;
    public $previous;
    public $list;
    public $next;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($h1, $previous, $list ,$next)
    {
        $this->h1 = $h1;
        $this->previous = $previous;
        $this->list = $list;
        $this->next = $next;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.content-header-previous-button');
    }
}
