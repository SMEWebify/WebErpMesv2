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

    /*
    |--------------------------------------------------------------------------
    | Options citables par les guides
    |--------------------------------------------------------------------------
    |
    | Nom court → clé de configuration lue comme un booléen. Un guide n'en cite
    | que par ce nom (`features:` dans l'en-tête, blocs `<!-- feature: nom -->`) :
    | un nom absent de la liste vaut « désactivée ».
    |
    */

    'features' => [
        'commercial' => 'branding.commercial',
        'nesting' => 'services.nestengine.enabled',
        'production-release' => 'production.require_release',
        'qualified-supplier' => 'purchases.require_qualified_supplier',
        'access-log' => 'audit.log_reads',
    ],

];
