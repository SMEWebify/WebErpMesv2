<?php

namespace App\View\Components;

use Illuminate\View\Component;

class AddressComponent extends Component
{
    public $id;
    public $label;
    public $adress;
    public $zipcode;
    public $city;
    public $county;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct( $id, $label,  $adress, $zipcode, $city,  $county)
    {
        $this->id = $id;
        $this->label = $label;
        $this->adress = $adress;
        $this->zipcode = $zipcode;
        $this->city = $city;
        $this->county = $county;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.address-component');
    }
}
