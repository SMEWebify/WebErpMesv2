<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ContactComponent extends Component
{
    public $id;
    public $function;
    public $name;
    public $firstname;
    public $mail;
    public $number;
    public $mobile;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($id, $function,  $name, $firstname, $mail,  $number,  $mobile)
    {
        $this->id = $id;
        $this->function = $function;
        $this->name = $name;
        $this->firstname = $firstname;
        $this->mail = $mail;
        $this->number = $number;
        $this->mobile = $mobile;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.contact-component');
    }
}
