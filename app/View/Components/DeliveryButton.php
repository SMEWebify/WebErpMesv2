<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DeliveryButton extends Component
{
    public $id;
    public $code;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct( $id, $code)
    {
        $this->id = $id;
        $this->code = $code;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.delivery-button');
    }
}
