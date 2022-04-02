<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ContentHeaderNewButton extends Component
{
    public $h1;
    public $modal;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($h1, $modal)
    {
        $this->h1 = $h1;
        $this->modal = $modal;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.content-header-new-button');
    }
}
