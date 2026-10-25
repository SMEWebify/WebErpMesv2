<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dossier des guides utilisateur
    |--------------------------------------------------------------------------
    |
    | Un guide = un sous-dossier : `guide.md` (en-tête YAML + Markdown), traductions
    | facultatives `guide.{locale}.md`, captures dans `img/`. Voir
    | App\Services\Guides\GuideLibrary pour le format de l'en-tête.
    |
    */

    'path' => env('GUIDES_PATH', resource_path('guides')),

];
