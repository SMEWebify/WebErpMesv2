<?php

namespace App\View\Components;

use App\Services\Documents\PdfLayoutRegistry;
use Illuminate\View\Component;

/**
 * Bouton PDF avec choix du modèle d'impression et de la langue du document.
 * Sans autre modèle que le modèle par défaut, la liste des modèles n'est pas affichée.
 */
class PdfPrintMenu extends Component
{
    /** @var array<string, \App\Services\Documents\PdfLayout> */
    public array $layouts;

    /** @var array<string, string> */
    public array $languages;

    public string $currentLanguage;

    public function __construct(public string $route, string $base = 'print/pdf-sales')
    {
        $registry              = app(PdfLayoutRegistry::class);
        $this->layouts         = $registry->available($base);
        $this->languages       = $registry->languages();
        $this->currentLanguage = app()->getLocale();
    }

    public function render()
    {
        return view('components.pdf-print-menu');
    }
}
