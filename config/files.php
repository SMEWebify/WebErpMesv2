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

    /*
    |--------------------------------------------------------------------------
    | Server-side previews (spreadsheets, e-mails)
    |--------------------------------------------------------------------------
    |
    | The GED shows spreadsheets and saved e-mails without downloading them:
    | the server reads the file and returns its content as JSON. A file above
    | max_bytes is not opened (download only); a spreadsheet preview stops at
    | max_rows x max_columns per sheet, the file itself staying untouched.
    |
    */

    'preview' => [
        'max_bytes' => (int) env('FILES_PREVIEW_MAX_BYTES', 15 * 1024 * 1024),
        'max_rows' => (int) env('FILES_PREVIEW_MAX_ROWS', 500),
        'max_columns' => (int) env('FILES_PREVIEW_MAX_COLUMNS', 50),
        // Inline pictures of an e-mail (cid:) above this size are not embedded.
        'max_inline_image_bytes' => 2 * 1024 * 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Editing in the desktop Office (Excel, Word)
    |--------------------------------------------------------------------------
    |
    | "Edit in Excel" opens the file in the Office installed on the workstation
    | (ms-excel: / ms-word: link) through a WebDAV address carrying a temporary
    | token; every save in Office uploads a new version of the file. Off by
    | default: the button does nothing on a computer without Office. The token
    | expires after ttl_minutes of inactivity.
    |
    */

    'office_edit' => [
        'enabled' => (bool) env('FILES_OFFICE_EDIT', false),
        'ttl_minutes' => (int) env('FILES_OFFICE_EDIT_TTL', 480),
        'schemes' => [
            'xlsx' => 'ms-excel',
            'xls' => 'ms-excel',
            'csv' => 'ms-excel',
            'docx' => 'ms-word',
            'doc' => 'ms-word',
        ],
    ],

];
