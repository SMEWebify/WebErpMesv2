<?php

/**
 * Branding configuration.
 *
 * Set APP_COMMERCIAL=true in .env to activate the commercial white-label mode
 * (Nest2Prod ERP branding + company logo).
 *
 * When APP_COMMERCIAL=false (default) the open-source WEM branding is used.
 */

$commercial = (bool) env('APP_COMMERCIAL', false);

return [

    'commercial' => $commercial,

    // Display names
    'app_name'      => $commercial ? 'Nest2Prod ERP' : 'WEM',
    'app_name_full' => $commercial ? 'Nest2Prod ERP' : 'WEB ERP MES',

    // Logo image path (relative to public/)
    // For commercial mode: place your logo at public/img/nest2prod-logo.png
    'logo_img' => $commercial
        ? 'img/nest2prod-logo.png'
        : 'vendor/adminlte/dist/img/simple-logo -R.PNG',

    'logo_alt' => $commercial ? 'Nest2Prod ERP' : 'WEM',
];
