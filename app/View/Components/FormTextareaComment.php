<?php

namespace App\View\Components;

use Illuminate\View\Component;

class FormTextareaComment extends Component
{
    public $comment;
    public $label;
    public $name;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct( $comment, $label = '' ,$name ="comment")
    {
        $this->comment = $comment;
        $this->label = $label;
        $this->name = $name;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.form-textarea-comment');
    }
}
