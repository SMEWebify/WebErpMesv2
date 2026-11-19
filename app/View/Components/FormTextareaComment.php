<?php

namespace App\View\Components;

use App\Services\AI\TextRewriter;
use Illuminate\View\Component;

class FormTextareaComment extends Component
{
    public $comment;
    public $label;
    public $name;
    public $maxLength;
    /** Adresse de « Réécrire sans fautes », null si non demandé ou sans fournisseur d'IA. */
    public ?string $rewriteUrl;

    /**
     * Create a new component instance.
     *
     * @param  bool  $rewrite  texte destiné au client : propose « Réécrire sans fautes »
     * @param  int|null  $maxLength  longueur acceptée par le champ (proposition plus longue signalée)
     * @return void
     */
    public function __construct( $comment, $label = '' ,$name ="comment", bool $rewrite = false, ?int $maxLength = null)
    {
        $this->comment = $comment;
        $this->label = $label;
        $this->name = $name;
        $this->maxLength = $maxLength;
        $this->rewriteUrl = $rewrite && app(TextRewriter::class)->isAvailable() ? route('ai.rewrite') : null;
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
