<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage disk and root
    |--------------------------------------------------------------------------
    |
    | Uploaded documents are kept outside the web root and streamed through an
    | authorized route. Only files uploaded before this was introduced still
    | live under public/ (see the legacy section below).
    |
    */

    'disk' => env('FILES_DISK', 'local'),

    'root' => 'private/files',

    /*
    |--------------------------------------------------------------------------
    | Maximum upload size, in kilobytes
    |--------------------------------------------------------------------------
    |
    | STEP assemblies and 3D meshes are far heavier than the office documents
    | the previous 10 MB limit was sized for. Remember to keep upload_max_filesize
    | and post_max_size in php.ini (and client_max_body_size in nginx) above it.
    |
    */

    'max_size' => (int) env('FILES_MAX_SIZE', 51200),

    /*
    |--------------------------------------------------------------------------
    | Legacy public directories
    |--------------------------------------------------------------------------
    |
    | Directories that used to be served straight from public/. The
    | wem:files:import command moves their content under
    | storage/app/private/legacy/{folder} and the matching routes then serve
    | them through authentication, so existing links keep working.
    |
    */

    'legacy_root' => 'private/legacy',

    'legacy_folders' => [
        'file',
        'photo',
        'drawing',
        'stl',
        'svg',
        'images/products',
    ],

];
